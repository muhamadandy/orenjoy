<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** Status pesanan yang dianggap sudah dibayar (masuk hitungan penjualan). */
    private const PAID_STATUSES = ['settlement', 'cooked', 'completed'];

    /** Rentang laporan maksimal (hari), supaya halaman tetap ringan. */
    private const MAX_DAYS = 366;

    public function index(Request $request): View
    {
        [$from, $to, $rangeError] = $this->resolveRange($request);

        $paid = fn () => Order::query()
            ->whereIn('status', self::PAID_STATUSES)
            ->whereBetween('created_at', [$from, $to]);

        $summary = $paid()
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(sub_total), 0) as sub_total, COALESCE(SUM(tax), 0) as tax, COALESCE(SUM(grand_total), 0) as total')
            ->first();

        $byPayment = $paid()
            ->selectRaw('payment_method, COUNT(*) as orders, COALESCE(SUM(grand_total), 0) as total')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $daily = $paid()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(sub_total) as sub_total, SUM(tax) as tax, SUM(grand_total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $cancelled = Order::query()
            ->where('status', 'cancelled')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Menu terlaris. Harga diambil dari salinan harga saat pesanan dibuat,
        // jadi hasilnya tidak berubah walau harga menu diubah kemudian.
        $topItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('items', 'items.id', '=', 'order_items.item_id')
            ->whereNull('orders.deleted_at')
            ->whereNull('order_items.deleted_at')
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('items.name as name, SUM(order_items.quantity) as qty, SUM(order_items.price * order_items.quantity) as revenue')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('qty')
            ->orderBy('items.name')
            ->limit(10)
            ->get();

        // Grafik: semua hari dalam rentang, hari tanpa penjualan bernilai 0.
        $chart = ['labels' => [], 'totals' => []];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            $chart['labels'][] = $date->format('d/m');
            $chart['totals'][] = (int) ($daily[$date->toDateString()]->total ?? 0);
        }

        $orderCount = (int) $summary->orders;
        $total = (int) $summary->total;

        return view('admin.reports.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rangeError' => $rangeError,
            'presets' => $this->presets(),
            'summary' => $summary,
            'average' => $orderCount > 0 ? intdiv($total, $orderCount) : 0,
            'byPayment' => $byPayment,
            'daily' => $daily,
            'cancelled' => $cancelled,
            'topItems' => $topItems,
            'chart' => $chart,
        ]);
    }

    /** Unduh pesanan yang sudah dibayar pada rentang tanggal sebagai CSV (buka di Excel). */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);

        $filename = 'laporan-penjualan-'.$from->toDateString().'_sd_'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');

            // BOM supaya Excel membaca huruf (UTF-8) dengan benar.
            fwrite($out, "\xEF\xBB\xBF");

            // Pemisah titik koma: terbuka rapi di Excel dengan pengaturan wilayah Indonesia.
            $write = fn (array $row) => fputcsv($out, $row, ';', '"', '');

            $write(['Kode', 'Tanggal', 'Jam', 'Nama', 'Meja', 'Pembayaran', 'Status', 'Subtotal', 'Pajak', 'Total']);

            Order::query()
                ->whereIn('status', self::PAID_STATUSES)
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->orderBy('id') // urutan pasti, supaya tidak ada baris terlewat atau ganda antar potongan
                ->chunk(500, function ($orders) use ($write) {
                    foreach ($orders as $order) {
                        $write([
                            $order->order_code,
                            $order->created_at->format('Y-m-d'),
                            $order->created_at->format('H:i'),
                            $this->csvSafe($order->customer_name),
                            $order->table_number ? 'Meja '.$order->table_number : 'Bawa pulang',
                            strtoupper($order->payment_method),
                            $order->status,
                            $order->sub_total,
                            $order->tax,
                            $order->grand_total,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Rentang tanggal dari ?from=&to= (format Y-m-d). Default: awal bulan ini sampai hari ini.
     * Input yang tidak valid diganti default, dan pesan alasannya dikembalikan.
     *
     * @return array{0: Carbon, 1: Carbon, 2: ?string}
     */
    private function resolveRange(Request $request): array
    {
        $defaultFrom = now()->startOfMonth()->startOfDay();
        $defaultTo = now()->endOfDay();

        $validator = Validator::make($request->query(), [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'date_format' => 'Format tanggal tidak valid.',
            'after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        if ($validator->fails()) {
            return [$defaultFrom, $defaultTo, $validator->errors()->first().' Ditampilkan: bulan ini.'];
        }

        $from = $request->query('from')
            ? Carbon::createFromFormat('Y-m-d', $request->query('from'))->startOfDay()
            : $defaultFrom;

        $to = $request->query('to')
            ? Carbon::createFromFormat('Y-m-d', $request->query('to'))->endOfDay()
            : ($request->query('from') ? $from->copy()->endOfDay() : $defaultTo);

        if ($to->gt($from->copy()->addDays(self::MAX_DAYS))) {
            return [$defaultFrom, $defaultTo, 'Rentang maksimal '.self::MAX_DAYS.' hari. Ditampilkan: bulan ini.'];
        }

        return [$from, $to, null];
    }

    /** Pilihan cepat rentang tanggal: [label => [from, to]]. */
    private function presets(): array
    {
        $today = now();

        return [
            'Hari ini' => [$today->toDateString(), $today->toDateString()],
            '7 hari terakhir' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            'Bulan ini' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            'Bulan lalu' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
        ];
    }

    /** Cegah "CSV injection": sel yang diawali = + - @ bisa dijalankan sebagai rumus oleh Excel. */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
