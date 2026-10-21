@extends('layouts.admin')

@php
    $editing = $user->exists;
    $roleNames = ['admin' => 'Admin', 'cashier' => 'Kasir', 'chef' => 'Koki'];
    $isSelf = $editing && $user->is(auth()->user());
@endphp

@section('title', $editing ? 'Ubah Karyawan' : 'Tambah Karyawan')
@section('heading', $editing ? 'Ubah Karyawan' : 'Tambah Karyawan')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <form action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}"
                      method="POST" autocomplete="off" novalidate>
                    @csrf
                    @if ($editing)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fullname" class="form-label">Nama lengkap</label>
                            <input type="text" id="fullname" name="fullname" maxlength="100" required
                                   value="{{ old('fullname', $user->fullname) }}"
                                   class="form-control @error('fullname') is-invalid @enderror">
                            @error('fullname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label">Username <span class="text-muted">(untuk login)</span></label>
                            <input type="text" id="username" name="username" maxlength="50" required autocapitalize="none"
                                   value="{{ old('username', $user->username) }}"
                                   class="form-control @error('username') is-invalid @enderror">
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" maxlength="100" required
                                   value="{{ old('email', $user->email) }}"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Nomor HP</label>
                            <input type="tel" id="phone" name="phone" maxlength="20" required inputmode="tel"
                                   value="{{ old('phone', $user->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="role_id" class="form-label">Peran</label>
                            <select id="role_id" name="role_id" required
                                    class="form-select @error('role_id') is-invalid @enderror">
                                <option value="">Pilih peran</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected((int) old('role_id', $user->role_id) === $role->id)>
                                        {{ $roleNames[$role->role_name] ?? $role->role_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if ($isSelf)
                                <div class="form-text">Peran akun Anda sendiri tidak bisa diubah.</div>
                            @endif
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">{{ $editing ? 'Password baru' : 'Password' }}</label>
                            <input type="password" id="password" name="password" minlength="8" maxlength="72"
                                   autocomplete="new-password" @required(! $editing)
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">
                                Minimal 8 karakter.
                                @if ($editing) Kosongkan jika tidak ingin mengubah password. @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Ulangi password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   minlength="8" maxlength="72" autocomplete="new-password" @required(! $editing)
                                   class="form-control">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ $editing ? 'Simpan perubahan' : 'Simpan' }}</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
