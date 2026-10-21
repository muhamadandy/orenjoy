<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans, PaymentService $payments)
    {
        $payload = $request->all();

        if (! $midtrans->validSignature($payload)) {
            return response()->json(['message' => 'invalid signature'], 403);
        }

        $payment = Payment::where('reference', $payload['order_id'] ?? '')->first();

        // Notifikasi uji dari dashboard / order_id tidak dikenal: balas 200 supaya tidak diulang-ulang.
        if (! $payment) {
            return response()->json(['message' => 'ignored']);
        }

        // Jangan percaya isi notifikasi mentah: tanya ulang status resmi ke Midtrans.
        try {
            $payments->apply($payment, $midtrans->status($payment->reference));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'retry later'], 500); // Midtrans akan mengirim ulang
        }

        return response()->json(['message' => 'ok']);
    }
}
