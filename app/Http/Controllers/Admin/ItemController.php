<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Support\IdMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->integer('category');

        $items = Item::with('category')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($categoryId > 0, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('cat_name')->get();

        return view('admin.items.index', compact('items', 'categories', 'search', 'categoryId'));
    }

    public function create(): View
    {
        return view('admin.items.form', [
            'item' => new Item(['is_active' => true]),
            'categories' => Category::orderBy('cat_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('items', 'public');
        }

        Item::create($data);

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    public function edit(Item $item): View
    {
        return view('admin.items.form', [
            'item' => $item,
            'categories' => Category::orderBy('cat_name')->get(),
        ]);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $data = $this->validated($request, $item);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('img')) {
            $this->deleteImage($item->img);
            $data['img'] = $request->file('img')->store('items', 'public');
        } elseif ($request->boolean('remove_img')) {
            $this->deleteImage($item->img);
            $data['img'] = null;
        }

        $item->update($data);

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Menu berhasil diperbarui.');
    }

    public function toggle(Item $item): RedirectResponse
    {
        $item->update(['is_active' => ! $item->is_active]);

        return back()->with(
            'success',
            $item->is_active
                ? "Menu \"{$item->name}\" diaktifkan."
                : "Menu \"{$item->name}\" dinonaktifkan."
        );
    }

    /**
     * Soft delete: menu hilang dari daftar dan halaman customer,
     * tapi riwayat pesanan lama tetap menampilkan namanya.
     * File foto sengaja dibiarkan.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Menu berhasil dihapus.');
    }

    private function validated(Request $request, ?Item $item = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('items', 'name')
                    ->ignore($item?->id)
                    ->whereNull('deleted_at'),
            ],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'description' => ['nullable', 'string', 'max:500'],
            'img' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], IdMessages::rules(), [
            'name' => 'Nama menu',
            'category_id' => 'Kategori',
            'price' => 'Harga',
            'description' => 'Deskripsi',
            'img' => 'Foto',
        ]);

        // File foto disimpan terpisah di store()/update().
        return Arr::except($data, 'img');
    }

    /** Hapus file foto lama dari storage (URL luar tidak disentuh). */
    private function deleteImage(?string $path): void
    {
        if ($path && ! Str::startsWith($path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($path);
        }
    }
}
