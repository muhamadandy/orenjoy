@extends('layouts.admin')

@section('title', 'Menu')
@section('heading', 'Menu')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Daftar Menu</h4>
                <a href="{{ route('admin.items.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Menu
                </a>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('admin.items.index') }}" class="row g-2 mb-4">
                    <div class="col-md-5">
                        <label for="q" class="visually-hidden">Cari menu</label>
                        <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama menu…">
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="visually-hidden">Kategori</label>
                        <select id="category" name="category" class="form-select">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->cat_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary flex-fill">Cari</button>
                        @if ($search !== '' || $categoryId > 0)
                            <a href="{{ route('admin.items.index') }}" class="btn btn-light">Reset</a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width:80px">Foto</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th class="text-end">Harga</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>
                                        <img src="{{ $item->image_url }}" alt="" width="56" height="56"
                                             class="rounded" style="object-fit:cover"
                                             onerror="this.onerror=null;this.src='{{ asset('assets/customer/img/placeholder.svg') }}'">
                                    </td>
                                    <td>
                                        <strong>{{ $item->name }}</strong>
                                        @if ($item->description)
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($item->description, 60) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $item->category?->cat_name ?? '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($item->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <form action="{{ route('admin.items.toggle', $item) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.items.edit', $item) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                        <form action="{{ route('admin.items.destroy', $item) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus menu ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        @if ($search !== '' || $categoryId > 0)
                                            Tidak ada menu yang cocok dengan pencarian.
                                        @else
                                            Belum ada menu. Klik "Tambah Menu" untuk mulai.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($items->hasPages())
                    <div class="mt-3">{{ $items->links() }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
