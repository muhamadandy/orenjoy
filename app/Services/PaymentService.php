<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentService
{
    public function __construct(private MidtransService $midtrans)
    {
    }

    public function enabled(): bool
    {
        return $this->midtrans->configured();
    }

    public function snapJsUrl(): string
    {
        return $this->midtrans->snapJsUrl();
    }

    /**
     * Pastikan pesanan punya halaman bayar Snap yang masih berlaku.
     * Nama method dipertahankan (createQris) supaya pemanggilnya tidak perlu diubah.
     */
    public function createQris(Order $order): Payment
    {
        if ($order->status !== 'pending' || $order->payment_method !== 'qris') {
            throw new RuntimeException('Pesanan ini tidak menunggu pembayaran QRIS.');
        }

        $latest = $order->payments()->latest('id')->first();

        if ($latest && $latest->status === 'pending') {
            $this->sync($latest);
            $latest->refresh();

            if ($latest->status === 'settlement' || ($latest->status === 'pending' && $latest->snap_token)) {
                return $latest;
            }
        }

        // Midtrans mewajibkan order_id unik: percobaan pertama = kode pesanan, berikutnya diberi akhiran.
        $attempt = $order->payments()->count() + 1;
        $reference = $attempt === 1 ? $order->order_code : $order->order_code.'-'.$attempt;

        $snap = $this->midtrans->createSnapToken($reference, (int) $order->grand_total, $order->customer_name);

        return $order->payments()->create([
            'reference' => $reference,
            'status' => 'pending',
            'amount' => (int) $order->grand_total,
            'snap_token' => $snap['token'],
            'payload' => $snap,
        ]);
    }

    /** Tanya status ke Midtrans (maksimal sekali per 10 detik per pembayaran). */
    public function sync(Payment $payment): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        if (! Cache::add('midtrans-sync:'.$payment->id, 1, 10)) {
            return;
        }

        try {
            $this->apply($payment, $this->midtrans->status($payment->reference));
        } catch (\Throwable $e) {
            // Sebelum pelanggan memilih metode di Snap, Midtrans membalas 404 "transaksi tidak ada". Itu normal.
            if (! str_contains($e->getMessage(), '(kode 404)')) {
                report($e);
            }
        }
    }

    public static function mapStatus(?string $transactionStatus, ?string $fraudStatus = null): ?string
    {
        return match ($transactionStatus) {
            'settlement' => 'settlement',
            'capture' => ($fraudStatus ?? 'accept') === 'accept' ? 'settlement' : 'pending',
            'pending' => 'pending',
            'expire' => 'expire',
            'cancel' => 'cancel',
            'deny', 'failure' => 'deny',
            default => null,
        };
    }

    /** Terapkan balasan Midtrans. Status final tidak diubah lagi, jadi notifikasi berulang/terlambat aman. */
    public function apply(Payment $payment, array $body): void
    {
        $mapped = self::mapStatus($body['transaction_status'] ?? null, $body['fraud_status'] ?? null);

        if ($mapped === null) {
            return;
        }

        DB::transaction(function () use ($payment, $body, $mapped) {
            $locked = Payment::lockForUpdate()->find($payment->id);

            if (! $locked) {
                return;
            }

            if ($mapped === 'settlement' && isset($body['gross_amount'])
                && (int) round((float) $body['gross_amount']) !== (int) $locked->amount) {
                Log::warning('Jumlah pembayaran Midtrans tidak cocok', [
                    'reference' => $locked->reference,
                    'expected' => $locked->amount,
                    'received' => $body['gross_amount'],
                ]);

                return;
            }

            // Uang masuk untuk pembayaran yang sudah kita tandai batal/kedaluwarsa: jangan diabaikan.
            // Catat sebagai dibayar supaya terlihat, lalu beri peringatan di log.
            $late = $mapped === 'settlement' && in_array($locked->status, ['cancel', 'expire'], true);

            if ($locked->status !== 'pending' && ! $late) {
                return;
            }

            $locked->update([
                'status' => $mapped,
                'transaction_id' => $body['transaction_id'] ?? $locked->transaction_id,
                'paid_at' => $mapped === 'settlement' ? now() : $locked->paid_at,
                'payload' => $body,
            ]);

            if ($mapped !== 'settlement') {
                return;
            }

            $order = Order::lockForUpdate()->find($locked->order_id);

            if ($order && $order->status === 'pending') {
                $order->update(['status' => 'settlement']);
            } elseif ($order) {
                Log::warning('PERLU TINDAKAN: QRIS dibayar untuk pesanan yang statusnya bukan pending. Periksa dan refund lewat dashboard Midtrans bila perlu.', [
                    'order' => $order->order_code,
                    'order_status' => $order->status,
                    'payment' => $locked->reference,
                    'amount' => $locked->amount,
                ]);
            }
        });
    }

    /** Hentikan pembayaran yang masih menunggu (saat staf membatalkan / menerima bayar manual). */
    public function cancelPending(Order $order): void
    {
        foreach ($order->payments()->where('status', 'pending')->get() as $payment) {
            $this->closeAtMidtrans($payment);

            // Tandai di sisi kita apa pun hasilnya, supaya pembayaran ini tidak ditawarkan lagi.
            // Kalau Midtrans ternyata tetap menerima uang, apply() akan mencatatnya sebagai pembayaran terlambat.
            $payment->update(['status' => 'cancel']);
        }
    }

    /** Coba batalkan di Midtrans; kalau ditolak, coba akhiri (expire). 404 = transaksi belum pernah dibuat. */
    private function closeAtMidtrans(Payment $payment): void
    {
        try {
            $this->midtrans->cancel($payment->reference);

            return;
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), '(kode 404)')) {
                return;
            }

            Log::warning('Pembatalan ke Midtrans ditolak, mencoba expire', [
                'reference' => $payment->reference,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->midtrans->expire($payment->reference);
        } catch (\Throwable $e) {
            Log::error('PERLU TINDAKAN: transaksi tidak bisa dibatalkan di Midtrans dan masih bisa dibayar', [
                'reference' => $payment->reference,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
