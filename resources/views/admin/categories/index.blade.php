@extends('layouts.admin')

@section('title', 'Kategori')
@section('heading', 'Kategori Menu')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Kategori</h4>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Kategori
                </a>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Jumlah Menu</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="fw-bold">{{ $category->cat_name }}</td>
                                    <td>{{ $category->description ?: '-' }}</td>
                                    <td class="text-center">{{ $category->items_count }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.categories.edit', $category) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i> Ubah
                                        </a>

                                        <form method="POST"
                                              action="{{ route('admin.categories.destroy', $category) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada kategori.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection