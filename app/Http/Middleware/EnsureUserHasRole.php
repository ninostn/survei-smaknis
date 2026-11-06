<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses hanya untuk role tertentu.
 *
 * Dipakai sebagai `role:admin`, `role:teacher`, atau `role:student`.
 * Pengguna yang belum login tetap harus ditangani middleware `auth`.
 * Enforement ada di server; penyembunyian menu bukan mekanisme keamanan.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user !== null && in_array($user->role, $roles, true), 403);

        return $next($request);
    }
}
