<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /** @param string ...$roles */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        if (! in_array(Auth::user()->role, $roles)) {
            abort(403, 'Tidak punya akses.');
        }

        return $next($request);
    }
}