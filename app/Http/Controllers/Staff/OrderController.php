<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Support\OrderFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    /** Form pesanan baru yang diisi kasir (pelanggan tanpa HP, atau bawa pulang). */
    public function create(): View
    {
        $categories = Category::query()
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('id')
            ->get()
            ->filter(fn (Category $category) => $category->items->isNotEmpty())
            ->values();

        $tables = DiningTable::where('is_active', true)->orderBy('number')->get();

        // Meja dianggap terisi kalau ada pesanan (selain yang dibatalkan) dalam beberapa menit terakhir.
        // Pesanan yang sudah diantar tetap dihitung, karena pelanggannya biasanya masih makan.
        $window = max(1, (int) config('orenjoy.table_occupied_minutes', 90));

        $occupancy = Order::query()
            ->whereNotNull('table_number')
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subMinutes($window))
            ->orderBy('created_at')
            ->get(['table_number', 'customer_name'])
            ->groupBy('table_number')
            ->map(function ($orders) {
                $names = $orders->map(fn ($order) => $order->customer_name ?: 'tanpa nama')->unique()->values();

                return [
                    'guests' => $names->take(3)->implode(', ').($names->count() > 3 ? ' dan lainnya' : ''),
                ];
            });

        return view('admin.orders.create', compact('categories', 'tables', 'occupancy', 'window'));
    }

    public function store(Request $request, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(['dine_in', 'takeaway'])],
            'table_id' => [
                'required_if:type,dine_in', 'nullable', 'integer',
                Rule::exists('tables', 'id')->where('is_active', true),
            ],
            'payment_method' => ['required', Rule::in(['tunai', 'qris'])],
            'note' => ['nullable', 'string', 'max:255'],
            'qty' => ['required', 'array', 'max:300'],
            'qty.*' => ['nullable', 'integer', 'between:0,50'],
        ], [
            'required' => ':attribute wajib diisi.',
            'required_if' => ':attribute wajib dipilih untuk makan di tempat.',
            'integer' => ':attribute harus berupa angka bulat.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'in' => ':attribute tidak valid.',
            'max.string' => ':attribute maksimal :max karakter.',
            'between.numeric' => 'Jumlah tiap menu harus antara :min dan :max.',
        ], [
            'customer_name' => 'Nama pelanggan',
            'type' => 'Jenis pesanan',
            'table_id' => 'Meja',
            'payment_method' => 'Metode pembayaran',
            'note' => 'Catatan',
            'qty' => 'Menu',
        ]);

        // Ambil hanya menu yang jumlahnya lebih dari 0.
        $lines = collect($data['qty'])
            ->map(fn ($qty, $id) => ['id' => (int) $id, 'qty' => (int) $qty])
            ->filter(fn (array $line) => $line['qty'] > 0)
            ->values()
            ->all();

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'Pilih minimal satu menu.']);
        }

        $table = $data['type'] === 'dine_in' ? DiningTable::find($data['table_id']) : null;

        $order = $orders->create($lines, [
            'customer_name' => $data['customer_name'],
            'table_number' => $table?->number,           // kosong = bawa pulang
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
            // Pelanggan membayar langsung di kasir: centang "sudah dibayar" melewati status menunggu bayar.
            'status' => $request->boolean('paid') ? 'settlement' : 'pending',
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', "Pesanan {$order->order_code} dibuat ({$this->statusText($order->status)}).");
    }

    public function updateStatus(Request $request, Order $order, PaymentService $payments): RedirectResponse
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

        // Pesanan dibatalkan, atau dibayar manual di kasir: matikan QR QRIS yang masih menunggu
        // supaya tidak bisa dibayar lagi oleh pelanggan. Dilakukan di luar transaksi karena memanggil Midtrans.
        if (in_array($target, ['cancelled', 'settlement'], true)) {
            $payments->cancelPending($updated);
        }

        return back()->with('success', "Pesanan {$updated->order_code}: ".OrderFlow::label($updated->status).'.');
    }

    private function statusText(string $status): string
    {
        return OrderFlow::label($status);
    }
}
