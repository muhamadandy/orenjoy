<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->user()->role?->role_name;
        $visible = OrderFlow::visibleStatuses($role);

        // Tab: "aktif" (belum selesai) atau satu status tertentu.
        $filter = (string) $request->query('status', 'aktif');
        if ($filter !== 'aktif' && ! in_array($filter, $visible, true)) {
            $filter = 'aktif';
        }

        $statuses = $filter === 'aktif'
            ? array_values(array_intersect(OrderFlow::ACTIVE, $visible))
            : [$filter];

        $orders = Order::query()
            ->with('orderItems.item')
            ->whereIn('status', $statuses)
            // Antrean: yang paling lama menunggu di atas. Riwayat: yang terbaru di atas.
            ->orderBy('created_at', $filter === 'aktif' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $counts = Order::query()
            ->whereIn('status', array_intersect(OrderFlow::ACTIVE, $visible))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.orders.index', [
            'orders' => $orders,
            'filter' => $filter,
            'visible' => $visible,
            'counts' => $counts,
            'activeCount' => $counts->sum(),
            'role' => $role,
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $role = $request->user()->role?->role_name;
        $target = (string) $request->input('status');

        // Kunci baris pesanan supaya dua staf yang menekan tombol bersamaan tidak saling menimpa.
        $updated = DB::transaction(function () use ($order, $target, $role) {
            $fresh = Order::lockForUpdate()->find($order->id);

            if (! $fresh || ! OrderFlow::can($fresh->status, $target, $role)) {
                return null;
            }

            $fresh->update(['status' => $target]);

            return $fresh;
        });

        if (! $updated) {
            return back()->with('error', 'Status pesanan tidak bisa diubah. Mungkin sudah diubah staf lain, atau Anda tidak berhak. Daftar sudah diperbarui.');
        }

        return back()->with('success', "Pesanan {$updated->order_code}: ".OrderFlow::label($updated->status).'.');
    }
}
