<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function store(Request $request): JsonResponse
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

        // Meja ditentukan server dari QR yang di-scan (tersimpan di session), bukan dari input pelanggan.
        // Dicek ulang di sini: meja yang sudah dihapus atau dinonaktifkan admin tidak bisa dipakai memesan.
        $table = session()->has('table_id')
            ? DiningTable::where('id', session('table_id'))->where('is_active', true)->first()
            : null;

        if (! $table) {
            session()->forget(['table_id', 'table_number']);

            throw ValidationException::withMessages([
                'table_number' => 'Meja belum dikenali. Scan QR code di meja Anda, lalu pesan lagi.',
            ]);
        }

        $ids = collect($data['items'])->pluck('id');

        // Menu yang nonaktif atau sudah dihapus (soft delete) tidak ikut terambil.
        $menu = Item::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($menu->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada menu di keranjang yang sudah tidak tersedia. Muat ulang halaman lalu pilih kembali.',
            ]);
        }

        $taxPercent = (int) config('orenjoy.tax_percent', 10);
        $subTotal = 0;
        $taxTotal = 0;
        $lines = [];

        foreach ($data['items'] as $row) {
            $item = $menu[$row['id']];
            $qty = (int) $row['qty'];

            $lineSub = $item->price * $qty;
            $lineTax = (int) round($lineSub * $taxPercent / 100);

            $lines[] = [
                'item_id' => $item->id,
                'quantity' => $qty,
                'price' => $item->price,          // harga satuan saat dipesan
                'tax' => $lineTax,                // pajak untuk baris ini
                'total_price' => $lineSub + $lineTax,
            ];

            $subTotal += $lineSub;
            $taxTotal += $lineTax;
        }

        $order = DB::transaction(function () use ($data, $table, $lines, $subTotal, $taxTotal) {
            $order = Order::create([
                'order_code' => $this->generateOrderCode(),
                'customer_name' => $data['customer_name'],
                'sub_total' => $subTotal,
                'tax' => $taxTotal,
                'grand_total' => $subTotal + $taxTotal,
                'status' => 'pending',
                'table_number' => $table->number,
                'payment_method' => $data['payment_method'],
                'note' => $data['note'] ?? null,
            ]);

            $order->orderItems()->createMany($lines);

            return $order;
        });

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

    /**
     * Kode pesanan mudah dibaca kasir, contoh: ORJ-261017-K7M2QX.
     * Huruf dan angka yang mirip (O/0, I/1, L) sengaja dibuang.
     */
    private function generateOrderCode(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, $max)];
            }

            $code = 'ORJ-'.now()->format('ymd').'-'.$suffix;
        } while (Order::withTrashed()->where('order_code', $code)->exists());

        return $code;
    }
}
