<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

/**
 * Pesanan contoh supaya dashboard, laporan penjualan, dan antrean dapur tidak kosong di situs demo.
 * Hanya dijalankan kalau ORENJOY_DEMO=true (lihat DatabaseSeeder).
 */
class DemoOrderSeeder extends Seeder
{
    private const NAMES = [
        'Budi', 'Siti', 'Andi', 'Rina', 'Dewi', 'Agus', 'Putri', 'Rizky',
        'Maya', 'Fajar', 'Lestari', 'Hendra', 'Nadia', 'Yoga', 'Sari', 'Bayu',
    ];

    public function run(OrderService $orders): void
    {
        // Seeder ini hanya menambah pesanan contoh; lewati kalau sudah pernah diisi.
        if (Order::withTrashed()->exists()) {
            return;
        }

        $menu = Item::where('is_active', true)->get(['id']);

        if ($menu->isEmpty()) {
            return;
        }

        $cashierId = User::where('username', 'kasir')->value('id');

        mt_srand(2026); // jumlah pesanan per hari dibuat konsisten antar reset

        // 14 hari ke belakang: pesanan selesai (sebagian kecil dibatalkan).
        for ($daysAgo = 14; $daysAgo >= 1; $daysAgo--) {
            $day = now()->subDays($daysAgo)->startOfDay();
            $count = $day->isWeekend() ? mt_rand(11, 16) : mt_rand(6, 11);

            for ($i = 0; $i < $count; $i++) {
                $when = $day->copy()->addHours(10)->addMinutes(mt_rand(0, 11 * 60));
                $status = mt_rand(1, 100) <= 8 ? 'cancelled' : 'completed';

                $this->makeOrder($orders, $menu, $cashierId, $status, $when);
            }
        }

        // Hari ini: antrean yang masih berjalan, supaya halaman kasir dan dapur terlihat hidup.
        foreach (['pending', 'settlement', 'settlement', 'cooked', 'completed'] as $status) {
            $this->makeOrder($orders, $menu, $cashierId, $status, now()->subMinutes(mt_rand(3, 90)));
        }
    }

    private function makeOrder(OrderService $orders, $menu, ?int $cashierId, string $status, $when): void
    {
        $lines = [];
        $picked = $menu->shuffle()->take(mt_rand(1, 4));

        foreach ($picked as $item) {
            $lines[] = ['id' => $item->id, 'qty' => mt_rand(1, 3)];
        }

        $takeaway = mt_rand(1, 100) <= 20;
        $byCashier = $takeaway || mt_rand(1, 100) <= 25;

        $order = $orders->create($lines, [
            'customer_name' => self::NAMES[mt_rand(0, count(self::NAMES) - 1)],
            'table_number' => $takeaway ? null : mt_rand(1, 10),
            'payment_method' => mt_rand(1, 100) <= 55 ? 'qris' : 'tunai',
            'note' => null,
            'status' => $status,
            'user_id' => $byCashier ? $cashierId : null,
        ]);

        // Mundurkan waktu pesanan (dan barisnya) ke jam yang dimaksud.
        $order->forceFill(['created_at' => $when, 'updated_at' => $when])->save();
        $order->orderItems()->update(['created_at' => $when, 'updated_at' => $when]);
    }
}
