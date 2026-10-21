@extends('layouts.admin')

@section('title', 'Manajemen Karyawan')
@section('heading', 'Manajemen Karyawan')

@section('content')
    @php
        $roleBadge = [
            'admin' => ['Admin', 'bg-danger'],
            'cashier' => ['Kasir', 'bg-primary'],
            'chef' => ['Koki', 'bg-warning text-dark'],
        ];
    @endphp

    <section class="section">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Daftar Karyawan</h4>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Karyawan
                </a>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Peran</th>
                                <th>Email</th>
                                <th>Nomor HP</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                @php([$roleLabel, $roleClass] = $roleBadge[$user->role?->role_name] ?? ['-', 'bg-secondary'])
                                <tr>
                                    <td>
                                        <strong>{{ $user->fullname }}</strong>
                                        @if ($user->is(auth()->user()))
                                            <span class="badge bg-light text-dark ms-1">Anda</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->username }}</td>
                                    <td><span class="badge {{ $roleClass }}">{{ $roleLabel }}</span></td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->phone }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Ubah</a>

                                        @unless ($user->is(auth()->user()))
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Yakin ingin menghapus karyawan ini? Ia tidak akan bisa login lagi.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada karyawan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
