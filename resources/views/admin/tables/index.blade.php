@extends('layouts.admin')

@section('title', 'Meja & QR')
@section('heading', 'Meja & QR')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Daftar Meja</h4>
                @if ($tables->where('is_active', true)->isNotEmpty())
                    <a href="{{ route('admin.tables.print') }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                        <i class="bi bi-printer"></i> Cetak semua QR
                    </a>
                @endif
            </div>

            <div class="card-body">
                <form action="{{ route('admin.tables.store') }}" method="POST" class="row g-2 mb-4" novalidate>
                    @csrf
                    <div class="col-sm-6 col-md-4">
                        <label for="number" class="visually-hidden">Nomor meja</label>
                        <input type="number" id="number" name="number" min="1" max="999" step="1" required
                               inputmode="numeric" placeholder="Nomor meja baru, contoh: 11"
                               value="{{ old('number') }}"
                               class="form-control @error('number') is-invalid @enderror">
                        @error('number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Tambah meja</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Meja</th>
                                <th>Status</th>
                                <th>Alamat QR</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tables as $table)
                                <tr>
                                    <td><strong>Meja {{ $table->number }}</strong></td>
                                    <td>
                                        @if ($table->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td><code class="small text-break">{{ $table->scanUrl() }}</code></td>
                                    <td class="text-end text-nowrap">
                                        @if ($table->is_active)
                                            <a href="{{ route('admin.tables.print', ['table' => $table->id]) }}"
                                               target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Cetak QR</a>
                                        @endif

                                        <form action="{{ route('admin.tables.toggle', $table) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                {{ $table->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.tables.regenerate', $table) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Ganti QR meja ini? QR lama yang sudah dicetak tidak akan berlaku lagi.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Ganti QR</button>
                                        </form>

                                        <form action="{{ route('admin.tables.destroy', $table) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus meja ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada meja. Tambahkan nomor meja di atas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <p class="text-muted small mb-0 mt-3">
                    Alamat QR mengikuti alamat situs yang sedang Anda buka. Sebelum mencetak untuk dipasang di meja,
                    buka panel admin lewat alamat situs yang sebenarnya (bukan alamat lokal).
                </p>
            </div>
        </div>
    </section>
@endsection
