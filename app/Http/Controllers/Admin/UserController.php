<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\IdMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Role yang termasuk karyawan (bukan pelanggan). */
    private const STAFF_ROLES = ['admin', 'cashier', 'chef'];

    public function index(): View
    {
        $users = User::with('role')
            ->whereHas('role', fn ($query) => $query->whereIn('role_name', self::STAFF_ROLES))
            ->orderBy('fullname')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(),
            'roles' => $this->staffRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // Password di-hash otomatis oleh cast 'hashed' di model User.
        User::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Karyawan {$data['fullname']} ditambahkan.");
    }

    public function edit(User $user): View
    {
        abort_unless($this->isStaff($user), 404);

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->staffRoles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->isStaff($user), 404);

        $data = $this->validated($request, $user);
        $isSelf = $user->is($request->user());

        // Mencegah admin mengunci dirinya sendiri dari panel.
        if ($isSelf && (int) $data['role_id'] !== (int) $user->role_id) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['role_id' => 'Anda tidak bisa mengubah peran akun Anda sendiri.']);
        }

        $passwordChanged = ! empty($data['password']);

        if (! $passwordChanged) {
            unset($data['password']); // kosong = password tidak diubah
        }

        $user->update($data);

        // Password karyawan lain diganti: paksa ia login ulang dengan password baru.
        if ($passwordChanged && ! $isSelf) {
            $this->endSessions($user);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Data {$user->fullname} diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->isStaff($user), 404);

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        // Soft delete: riwayat pesanan yang diinput karyawan ini tetap utuh,
        // dan akunnya langsung tidak bisa dipakai login.
        $user->delete();
        $this->endSessions($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Karyawan {$user->fullname} dihapus.");
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $password = ['string', 'min:8', 'max:72', 'confirmed'];

        return $request->validate([
            // Username dan email yang pernah dipakai karyawan terhapus tetap dianggap terpakai,
            // sesuai aturan unik di database.
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'fullname' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:100',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{6,20}$/'],
            'role_id' => [
                'required', 'integer',
                Rule::exists('roles', 'id')->where(
                    fn ($query) => $query->whereIn('role_name', self::STAFF_ROLES)->whereNull('deleted_at')
                ),
            ],
            'password' => $user ? array_merge(['nullable'], $password) : array_merge(['required'], $password),
        ], IdMessages::rules(), [
            'username' => 'Username',
            'fullname' => 'Nama lengkap',
            'email' => 'Email',
            'phone' => 'Nomor HP',
            'role_id' => 'Peran',
            'password' => 'Password',
        ]);
    }

    private function staffRoles()
    {
        return Role::whereIn('role_name', self::STAFF_ROLES)->orderBy('id')->get();
    }

    private function isStaff(User $user): bool
    {
        return in_array($user->role?->role_name, self::STAFF_ROLES, true);
    }

    /** Keluarkan karyawan dari semua perangkat (hanya berlaku bila session disimpan di database). */
    private function endSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
