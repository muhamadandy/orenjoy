<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Halaman menu: semua kategori beserta menu yang aktif.
     */
    public function index(): View
    {
        // Meja di session bisa sudah dihapus atau dinonaktifkan admin: kalau begitu, lupakan.
        if (session()->has('table_id')) {
            $stillValid = DiningTable::where('id', session('table_id'))
                ->where('is_active', true)
                ->exists();

            if (! $stillValid) {
                session()->forget(['table_id', 'table_number']);
            }
        }

        $categories = Category::query()
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('id')
            ->get()
            ->filter(fn (Category $category) => $category->items->isNotEmpty())
            ->values();

        return view('customer.menu', compact('categories'));
    }

    /**
     * Tujuan QR code di meja, contoh: https://domain-kamu.com/meja/k3x9...
     * Token acak dicocokkan ke tabel meja, meja disimpan di session,
     * lalu pelanggan diarahkan ke menu. Token tidak dikenal / meja nonaktif = 404.
     */
    public function table(string $token): RedirectResponse
    {
        $table = DiningTable::where('token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        session([
            'table_id' => $table->id,
            'table_number' => $table->number,
        ]);

        return redirect()->route('menu');
    }
}
