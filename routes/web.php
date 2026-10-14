<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::get('/dashboard', function () {
    $role = Auth::user()->role;

    if ($role === 'admin') {
        return redirect('/admin');
    }

    if ($role === 'guru') {
        return redirect('/guru');
    }

    return redirect('/murid');
})->middleware('auth');

Route::get('/admin', function () {
    return view('admin');
})->middleware(['auth', 'role:admin']);

Route::get('/guru', function () {
    return view('guru');
})->middleware(['auth', 'role:guru,admin']);

Route::get('/murid', function () {
    return view('murid');
})->middleware('auth');