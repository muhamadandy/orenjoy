<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    /** Buat QR baru (setelah kedaluwarsa atau kalau pembuatan pertama gagal). */
    public function qris(string $code, PaymentService $payments)
    {
        $order = Order::where('order_code', $code)->firstOrFail();

        if (! $payments->enabled()) {
            return back()->with('payment_error', 'Pembayaran QRIS online belum tersedia. Silakan bayar di kasir.');
        }

        try {
            $payments->createQris($order);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('payment_error', 'QR belum bisa dibuat. Coba lagi sebentar atau bayar di kasir.');
        }

        return back();
    }

    /** Dipanggil halaman pesanan setiap beberapa detik. */
    public function status(string $code, PaymentService $payments)
    {
        $order = Order::where('order_code', $code)->firstOrFail();
        $payment = $order->payments()->latest('id')->first();

        if ($payment) {
            $payments->sync($payment);
            $payment->refresh();
            $order->refresh();
        }

        return response()->json([
            'order_status' => $order->status,
            'payment_status' => $payment?->status,
        ]);
    }
}
