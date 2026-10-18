<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\IdMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('items')->orderBy('id')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->items()->exists()) {
            return back()->with(
                'error',
                "Kategori \"{$category->cat_name}\" masih punya menu. Pindahkan atau hapus menunya dulu."
            );
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'cat_name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories', 'cat_name')
                    ->ignore($category?->id)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ], IdMessages::rules(), [
            'cat_name' => 'Nama kategori',
            'description' => 'Deskripsi',
        ]);

        // Kolom description di database tidak boleh NULL.
        $data['description'] = $data['description'] ?? '';

        return $data;
    }
}