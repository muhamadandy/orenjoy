@extends('layouts.admin')

@php($editing = $item->exists)

@section('title', $editing ? 'Ubah Menu' : 'Tambah Menu')
@section('heading', $editing ? 'Ubah Menu' : 'Tambah Menu')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <form action="{{ $editing ? route('admin.items.update', $item) : route('admin.items.store') }}"
                      method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    @if ($editing)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Nama menu</label>
                            <input type="text" id="name" name="name" maxlength="100" required
                                   value="{{ old('name', $item->name) }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select id="category_id" name="category_id" required
                                    class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('category_id', $item->category_id) === $category->id)>
                                        {{ $category->cat_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="price" class="form-label">Harga (Rp)</label>
                            <input type="number" id="price" name="price" min="0" max="100000000" step="1" required
                                   inputmode="numeric" value="{{ old('price', $item->price) }}"
                                   class="form-control @error('price') is-invalid @enderror">
                            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Deskripsi <span class="text-muted">(opsional)</span></label>
                            <textarea id="description" name="description" rows="3" maxlength="500"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $item->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="img" class="form-label">Foto <span class="text-muted">(JPG, PNG, atau WEBP, maks. 2 MB)</span></label>

                            @if ($editing && $item->img)
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="{{ $item->image_url }}" alt="Foto saat ini" width="96" height="96"
                                         class="rounded" style="object-fit:cover">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remove_img" name="remove_img" value="1"
                                               @checked(old('remove_img'))>
                                        <label class="form-check-label" for="remove_img">Hapus foto ini</label>
                                    </div>
                                </div>
                            @endif

                            <input type="file" id="img" name="img" accept="image/jpeg,image/png,image/webp"
                                   class="form-control @error('img') is-invalid @enderror">
                            @error('img') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if ($editing && $item->img)
                                <div class="form-text">Pilih file baru untuk mengganti foto.</div>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                                       @checked(old('is_active', $editing ? $item->is_active : true))>
                                <label class="form-check-label" for="is_active">Tampilkan di menu pelanggan</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ $editing ? 'Simpan perubahan' : 'Simpan' }}</button>
                        <a href="{{ route('admin.items.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
