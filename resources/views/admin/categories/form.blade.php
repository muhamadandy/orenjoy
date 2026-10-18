@extends('layouts.admin')

@section('title', $category->exists ? 'Ubah Kategori' : 'Tambah Kategori')
@section('heading', $category->exists ? 'Ubah Kategori' : 'Tambah Kategori')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <form method="POST"
                      action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
                    @csrf
                    @if ($category->exists)
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="cat_name" class="form-label">Nama kategori</label>
                        <input id="cat_name" name="cat_name" type="text" maxlength="100" required autofocus
                               class="form-control @error('cat_name') is-invalid @enderror"
                               value="{{ old('cat_name', $category->cat_name) }}">
                        @error('cat_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">
                            Deskripsi <span class="text-muted">(opsional)</span>
                        </label>
                        <input id="description" name="description" type="text" maxlength="255"
                               class="form-control @error('description') is-invalid @enderror"
                               value="{{ old('description', $category->description) }}">
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection