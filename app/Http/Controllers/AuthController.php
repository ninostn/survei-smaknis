<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'nis' => 'required|string',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($data)) {
            return back()->withErrors([
                'nis' => 'NIS atau password salah.',
            ])->onlyInput('nis');
        }

        $request->session()->regenerate();

        $role = Auth::user()->role;

        if ($role === 'admin') {
            return redirect()->intended('/admin');
        }

        if ($role === 'guru') {
            return redirect()->intended('/guru');
        }

        return redirect()->intended('/murid');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}