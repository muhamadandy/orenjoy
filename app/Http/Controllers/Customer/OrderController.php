<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Terima pesanan dari keranjang.
     *
     * Browser hanya mengirim id menu dan jumlahnya. Nama dan harga selalu
     * diambil ulang dari database, jadi harga tidak bisa dimanipulasi dari HP.
     */
    public function store(Request $request, OrderService $orders): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:60'],
            'payment_method' => ['required', Rule::in(['tunai', 'qris'])],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'between:1,50'],
        ], [
            'customer_name.required' => 'Nama wajib diisi.',
            'customer_name.max' => 'Nama maksimal 60 karakter.',
            'payment_method.required' => 'Pilih metode pembayaran.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'note.max' => 'Catatan maksimal 255 karakter.',
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Keranjang masih kosong.',
            'items.max' => 'Terlalu banyak jenis menu dalam satu pesanan (maksimal 50).',
            'items.*.id.required' => 'Data menu tidak valid.',
            'items.*.id.integer' => 'Data menu tidak valid.',
            'items.*.id.distinct' => 'Ada menu yang tercatat dua kali di keranjang.',
            'items.*.qty.required' => 'Jumlah menu tidak valid.',
            'items.*.qty.integer' => 'Jumlah menu tidak valid.',
            'items.*.qty.between' => 'Jumlah tiap menu harus antara 1 dan 50.',
        ]);

        // Meja ditentukan server dari QR yang di-scan HP ini, bukan dari input pelanggan.
        // Gagal kalau belum scan, masa berlakunya habis, atau mejanya sudah dihapus/dinonaktifkan.
        $table = DiningTable::fromSession();

        if (! $table) {
            throw ValidationException::withMessages([
                'table_number' => 'Meja belum dikenali atau sesi scan sudah berakhir. Scan QR code di meja Anda lagi, lalu pesan.',
            ]);
        }

        $order = $orders->create($data['items'], [
            'customer_name' => $data['customer_name'],
            'table_number' => $table->number,
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'user_id' => null,
        ]);


        // Ingat kode pesanan di session HP ini supaya muncul di "Pesanan saya" (maksimal 10 terakhir).
        session([
            'order_codes' => collect(session('order_codes', []))
                ->push($order->order_code)
                ->unique()
                ->take(-10)
                ->values()
                ->all(),
        ]);

        // Path relatif supaya tetap benar walau diakses lewat IP LAN atau domain lain.
        return response()->json([
            'redirect' => route('order.show', ['code' => $order->order_code], false),
        ]);
    }

    /**
     * Halaman konfirmasi dan status pesanan.
     */
    public function show(string $code): View
    {
        $order = Order::with('orderItems.item')
            ->where('order_code', $code)
            ->firstOrFail();

        return view('customer.order-success', compact('order'));
    }
}
