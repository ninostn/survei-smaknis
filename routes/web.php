<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = Auth::user();

    if ($user === null) {
        return redirect()->route('login');
    }

    return redirect()->to(match ($user->role) {
        'admin' => '/admin/dashboard',
        'teacher' => '/teacher/dashboard',
        default => '/student/dashboard',
    });
});

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/admin/dashboard', fn () => view('admin.dashboard'))
        ->middleware('role:admin')->name('admin.dashboard');

    Route::get('/teacher/dashboard', fn () => view('teacher.dashboard'))
        ->middleware('role:teacher')->name('teacher.dashboard');

    Route::get('/student/dashboard', fn () => view('student.dashboard'))
        ->middleware('role:student')->name('student.dashboard');

    Route::prefix('admin/users')
        ->name('admin.users.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('create');
            Route::post('/teachers', [\App\Http\Controllers\Admin\UserController::class, 'storeTeacher'])->name('teachers.store');
            Route::post('/students', [\App\Http\Controllers\Admin\UserController::class, 'storeStudent'])->name('students.store');
            Route::get('/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])
                ->whereNumber('user')->name('edit');
            Route::put('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])
                ->whereNumber('user')->name('update');
            Route::patch('/{user}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])
                ->whereNumber('user')->name('reset-password');
        });
});
