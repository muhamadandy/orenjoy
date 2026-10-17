<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Pemakaian di route: EnsureRole::class.':admin' atau EnsureRole::class.':admin,cashier'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role?->role_name;

        abort_unless(
            $role !== null && in_array($role, $roles, true),
            403,
            'Anda tidak punya akses ke halaman ini.'
        );

        return $next($request);
    }
}