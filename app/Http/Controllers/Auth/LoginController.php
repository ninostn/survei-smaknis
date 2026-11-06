<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Batas percobaan login gagal per username+IP.
     */
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request): RedirectResponse|\Illuminate\View\View
    {
        if ($request->user()) {
            return redirect()->to($this->dashboardFor($request->user()));
        }

        return view('auth.login');
    }

    /**
     * Proses login berdasarkan username dan password.
     *
     * Pesan error sengaja generik dan sama untuk semua kegagalan
     * (username tidak terdaftar, password salah, atau akun nonaktif)
     * supaya tidak membocorkan akun mana yang ada.
     */
    public function login(Request $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->to($this->dashboardFor($request->user()));
        }

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ])->status(429);
        }

        // is_active dipakai sebagai kondisi query sehingga akun nonaktif
        // gagal login dengan pesan generik yang sama.
        $attempted = Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => true,
        ]);

        if (! $attempted) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'username' => 'Kredensial yang dimasukkan tidak valid.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Regenerasi session untuk mencegah session fixation.
        $request->session()->regenerate();

        return redirect()->intended($this->dashboardFor($request->user()));
    }

    /**
     * Logout hanya lewat POST (route). Invalidasi session dan
     * regenerasi token CSRF.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(mb_strtolower((string) $request->input('username'))).'|'.$request->ip();
    }

    private function dashboardFor(User $user): string
    {
        return match ($user->role) {
            'admin' => '/admin/dashboard',
            'teacher' => '/teacher/dashboard',
            default => '/student/dashboard',
        };
    }
}
