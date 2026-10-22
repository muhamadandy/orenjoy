<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    /** Snap butuh server key (membuat token) dan client key (membuka popup di browser). */
    public function configured(): bool
    {
        return filled(config('midtrans.server_key')) && filled(config('midtrans.client_key'));
    }

    public function snapJsUrl(): string
    {
        return config('midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /** Buat token Snap untuk satu pembayaran. Mengembalikan ['token' => ..., 'redirect_url' => ...]. */
    public function createSnapToken(string $reference, int $amount, ?string $customerName = null): array
    {
        $payload = [
            'transaction_details' => [
                'order_id' => $reference,
                'gross_amount' => $amount,
            ],
            'expiry' => [
                'duration' => max(1, (int) config('midtrans.expiry_minutes', 30)),
                'unit' => 'minute',
            ],
        ];

        if (filled($customerName)) {
            $payload['customer_details'] = ['first_name' => mb_substr($customerName, 0, 50)];
        }

        $enabled = config('midtrans.enabled_payments', []);
        if ($enabled !== []) {
            $payload['enabled_payments'] = $enabled;
        }

        $base = config('midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';

        $response = $this->http($base)->post('/snap/v1/transactions', $payload);
        $body = $response->json() ?? [];

        if (! $response->successful() || empty($body['token'])) {
            $message = $body['error_messages'][0] ?? $body['status_message'] ?? 'Gagal membuat halaman pembayaran';

            throw new RuntimeException($message.' (HTTP '.$response->status().')');
        }

        return $body;
    }

    public function status(string $reference): array
    {
        $response = $this->http($this->apiBase())->get('/v2/'.rawurlencode($reference).'/status');

        // Midtrans memakai kode berbeda untuk tiap status: 200 sukses, 201 menunggu bayar,
        // 202 ditolak, 407 kedaluwarsa. Semuanya balasan sah yang berisi transaction_status.
        return $this->checked($response->json() ?? [], ['200', '201', '202', '407'], 'Gagal membaca status pembayaran');
    }

    public function cancel(string $reference): array
    {
        $response = $this->http($this->apiBase())->post('/v2/'.rawurlencode($reference).'/cancel');

        return $this->checked($response->json() ?? [], ['200'], 'Gagal membatalkan transaksi');
    }

    /** SHA512(order_id + status_code + gross_amount + server key) */
    public function validSignature(array $payload): bool
    {
        $serverKey = (string) config('midtrans.server_key');

        if ($serverKey === '' || empty($payload['signature_key'])) {
            return false;
        }

        $expected = hash('sha512',
            ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$serverKey
        );

        return hash_equals($expected, (string) $payload['signature_key']);
    }

    private function apiBase(): string
    {
        return config('midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    private function http(string $base): PendingRequest
    {
        return Http::baseUrl($base)
            ->withBasicAuth((string) config('midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    /** Midtrans sering membalas HTTP 200 walau gagal; hasil sebenarnya ada di status_code pada badan balasan. */
    private function checked(array $body, array $okCodes, string $fallbackMessage): array
    {
        $code = (string) ($body['status_code'] ?? '');

        if (! in_array($code, $okCodes, true)) {
            throw new RuntimeException(($body['status_message'] ?? $fallbackMessage).' (kode '.($code ?: '-').')');
        }

        return $body;
    }
}
