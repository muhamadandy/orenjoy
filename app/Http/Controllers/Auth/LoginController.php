<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Role yang boleh masuk panel => nama route tujuan setelah login.
     * Kasir dan koki dibuka nanti, saat halaman mereka sudah dibuat.
     */
    private const PANEL_HOME = [
        'admin' => 'admin.dashboard',
        // 'cashier' => 'admin.orders.index',
        // 'chef' => 'admin.kitchen.index',
    ];

    public function create(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user && $this->homeFor($user)) {
            return redirect()->route($this->homeFor($user));
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Username atau password salah.']);
        }

        $home = $this->homeFor(Auth::user());

        if ($home === null) {
            $this->logout($request);

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Akun ini belum punya akses ke panel.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route($home));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logout($request);

        return redirect()->route('login');
    }

    private function homeFor(User $user): ?string
    {
        return self::PANEL_HOME[$user->role?->role_name] ?? null;
    }

    private function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}