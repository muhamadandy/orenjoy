<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Status pesanan yang dianggap sudah dibayar (masuk hitungan penjualan). */
    private const PAID_STATUSES = ['settlement', 'cooked', 'completed'];

    public function __invoke(): View
    {
        $stats = [
            'orders_total' => Order::count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'items' => Item::count(),
            'staff' => User::whereHas(
                'role',
                fn ($query) => $query->whereIn('role_name', ['admin', 'cashier', 'chef'])
            )->count(),
        ];

        // Penjualan 7 hari terakhir (hari ini + 6 hari sebelumnya)
        $from = now()->subDays(6)->startOfDay();

        $perDay = Order::query()
            ->whereIn('status', self::PAID_STATUSES)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(grand_total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $chart = ['labels' => [], 'totals' => []];

        for ($i = 0; $i < 7; $i++) {
            $date = $from->copy()->addDays($i);

            $chart['labels'][] = $date->format('d/m');
            $chart['totals'][] = (int) ($perDay[$date->toDateString()] ?? 0);
        }

        return view('admin.dashboard', compact('stats', 'chart'));
    }
}