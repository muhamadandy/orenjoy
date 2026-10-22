<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans, PaymentService $payments)
    {
        $payload = $request->all();

        if (! $midtrans->validSignature($payload)) {
            Log::warning('Webhook Midtrans: tanda tangan tidak valid', ['order_id' => $payload['order_id'] ?? null]);

            return response()->json(['message' => 'invalid signature'], 403);
        }

        $payment = Payment::where('reference', $payload['order_id'] ?? '')->first();

        // Notifikasi uji dari dashboard / order_id tidak dikenal: balas 200 supaya tidak diulang-ulang.
        if (! $payment) {
            Log::info('Webhook Midtrans: order_id tidak dikenal, diabaikan', ['order_id' => $payload['order_id'] ?? null]);

            return response()->json(['message' => 'ignored']);
        }

        // Jangan percaya isi notifikasi mentah: tanya ulang status resmi ke Midtrans.
        try {
            $payments->apply($payment, $midtrans->status($payment->reference));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'retry later'], 500); // Midtrans akan mengirim ulang
        }

        Log::info('Webhook Midtrans: diterima dan diproses', [
            'order_id' => $payload['order_id'],
            'transaction_status' => $payload['transaction_status'] ?? null,
        ]);

        return response()->json(['message' => 'ok']);
    }
}
