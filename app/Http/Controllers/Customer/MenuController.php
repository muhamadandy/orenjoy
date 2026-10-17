<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Halaman menu: semua kategori beserta menu yang aktif.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('id')
            ->get()
            ->filter(fn (Category $category) => $category->items->isNotEmpty())
            ->values();

        return view('customer.menu', compact('categories'));
    }

    /**
     * Tujuan QR code di meja, contoh: https://domain-kamu.com/meja/5
     * Nomor meja disimpan di session lalu pelanggan diarahkan ke menu.
     */
    public function table(int $number): RedirectResponse
    {
        abort_unless($number >= 1 && $number <= 999, 404);

        session(['table_number' => $number]);

        return redirect()->route('menu');
    }
}
