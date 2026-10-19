<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat pesanan dibuat: dipakai oleh pelanggan (scan QR) dan kasir.
 * Nama dan harga menu selalu diambil dari database, tidak pernah dari input.
 */
class OrderService
{
    /**
     * @param  array<int, array{id:int, qty:int}>  $lines  menu yang dipesan
     * @param  array{customer_name:string, table_number:?int, payment_method:string, note:?string, status:string, user_id:?int}  $attrs
     */
    public function create(array $lines, array $attrs): Order
    {
        $ids = collect($lines)->pluck('id');

        // Menu yang nonaktif atau sudah dihapus (soft delete) tidak ikut terambil.
        $menu = Item::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($menu->count() !== $ids->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada menu yang sudah tidak tersedia. Muat ulang halaman lalu pilih kembali.',
            ]);
        }

        $taxPercent = (int) config('orenjoy.tax_percent', 10);
        $subTotal = 0;
        $taxTotal = 0;
        $rows = [];

        foreach ($lines as $line) {
            $item = $menu[$line['id']];
            $qty = (int) $line['qty'];

            $lineSub = $item->price * $qty;
            $lineTax = (int) round($lineSub * $taxPercent / 100);

            $rows[] = [
                'item_id' => $item->id,
                'quantity' => $qty,
                'price' => $item->price,          // harga satuan saat dipesan
                'tax' => $lineTax,                // pajak untuk baris ini
                'total_price' => $lineSub + $lineTax,
            ];

            $subTotal += $lineSub;
            $taxTotal += $lineTax;
        }

        return DB::transaction(function () use ($attrs, $rows, $subTotal, $taxTotal) {
            $order = Order::create([
                'order_code' => $this->generateOrderCode(),
                'user_id' => $attrs['user_id'] ?? null,
                'customer_name' => $attrs['customer_name'],
                'sub_total' => $subTotal,
                'tax' => $taxTotal,
                'grand_total' => $subTotal + $taxTotal,
                'status' => $attrs['status'],
                'table_number' => $attrs['table_number'],
                'payment_method' => $attrs['payment_method'],
                'note' => $attrs['note'] ?? null,
            ]);

            $order->orderItems()->createMany($rows);

            return $order;
        });
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