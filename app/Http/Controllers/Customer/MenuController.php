<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Support\OrderFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Halaman menu: semua kategori beserta menu yang aktif.
     */
    public function index(): View
    {
        // Membersihkan meja di session yang sudah kedaluwarsa, dihapus, atau dinonaktifkan.
        DiningTable::fromSession();

        $categories = Category::query()
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('id')
            ->get()
            ->filter(fn (Category $category) => $category->items->isNotEmpty())
            ->values();

        $myOrders = $this->myActiveOrders();

        return view('customer.menu', compact('categories', 'myOrders'));
    }

    /**
     * Tujuan QR code di meja, contoh: https://domain-kamu.com/meja/k3x9...
     * Token acak dicocokkan ke tabel meja, meja diingat di session HP pelanggan,
     * lalu pelanggan diarahkan ke menu. Token tidak dikenal / meja nonaktif = 404.
     */
    public function table(string $token): RedirectResponse
    {
        $table = DiningTable::where('token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        DiningTable::rememberInSession($table);

        return redirect()->route('menu');
    }

    /**
     * Pesanan milik HP ini yang belum selesai ("Pesanan saya").
     * Kode pesanan disimpan di session saat checkout; yang sudah selesai
     * atau dibatalkan dibuang dari daftar.
     */
    private function myActiveOrders(): Collection
    {
        $codes = (array) session('order_codes', []);

        if ($codes === []) {
            return collect();
        }

        $orders = Order::query()
            ->whereIn('order_code', $codes)
            ->whereIn('status', OrderFlow::ACTIVE)
            ->orderBy('created_at')
            ->get();

        session(['order_codes' => $orders->pluck('order_code')->all()]);

        return $orders;
    }
}
