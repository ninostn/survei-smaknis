<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\MuridSurveyController;
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

Route::middleware(['auth', 'role:admin,guru'])->group(function () {
    Route::resource('surveys', SurveyController::class)->except(['show']);
    Route::patch('surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
});

// ROUTE SURVEI MURID
Route::middleware(['auth', 'role:murid'])
    ->prefix('murid')
    ->name('murid.')
    ->group(function () {
        Route::get('/surveys', [MuridSurveyController::class, 'index'])
            ->name('surveys.index');

        Route::get('/surveys/history', [MuridSurveyController::class, 'history'])
            ->name('surveys.history');

        Route::get('/surveys/{survey}', [MuridSurveyController::class, 'show'])
            ->name('surveys.show');

        Route::post('/surveys/{survey}', [MuridSurveyController::class, 'store'])
            ->name('surveys.store');
    });