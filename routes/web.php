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

    Route::prefix('admin/classes')
        ->name('admin.classes.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SchoolClassController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\SchoolClassController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\SchoolClassController::class, 'store'])->name('store');
            Route::get('/{class}/edit', [\App\Http\Controllers\Admin\SchoolClassController::class, 'edit'])
                ->whereNumber('class')->name('edit');
            Route::put('/{class}', [\App\Http\Controllers\Admin\SchoolClassController::class, 'update'])
                ->whereNumber('class')->name('update');
            Route::patch('/{class}/toggle', [\App\Http\Controllers\Admin\SchoolClassController::class, 'toggle'])
                ->whereNumber('class')->name('toggle');
        });

    Route::prefix('admin/subjects')
        ->name('admin.subjects.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SubjectController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\SubjectController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\SubjectController::class, 'store'])->name('store');
            Route::get('/{subject}/edit', [\App\Http\Controllers\Admin\SubjectController::class, 'edit'])
                ->whereNumber('subject')->name('edit');
            Route::put('/{subject}', [\App\Http\Controllers\Admin\SubjectController::class, 'update'])
                ->whereNumber('subject')->name('update');
        });

    Route::prefix('admin/teaching-assignments')
        ->name('admin.teaching-assignments.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'store'])->name('store');
            Route::get('/{assignment}/edit', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'edit'])
                ->whereNumber('assignment')->name('edit');
            Route::put('/{assignment}', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'update'])
                ->whereNumber('assignment')->name('update');
            Route::delete('/{assignment}', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'destroy'])
                ->whereNumber('assignment')->name('destroy');
        });

    Route::prefix('surveys')
        ->name('surveys.')
        ->middleware(['role:admin,teacher'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\SurveyController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\SurveyController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\SurveyController::class, 'store'])->name('store');
            Route::get('/{survey}/edit', [\App\Http\Controllers\SurveyController::class, 'edit'])
                ->whereNumber('survey')->name('edit');
            Route::put('/{survey}', [\App\Http\Controllers\SurveyController::class, 'update'])
                ->whereNumber('survey')->name('update');
            Route::delete('/{survey}', [\App\Http\Controllers\SurveyController::class, 'destroy'])
                ->whereNumber('survey')->name('destroy');

            Route::get('/{survey}/publish', [\App\Http\Controllers\SurveyController::class, 'publishForm'])
                ->whereNumber('survey')->name('publish.create');
            Route::patch('/{survey}/publish', [\App\Http\Controllers\SurveyController::class, 'publish'])
                ->whereNumber('survey')->name('publish');
            Route::patch('/{survey}/close', [\App\Http\Controllers\SurveyController::class, 'close'])
                ->whereNumber('survey')->name('close');

            Route::prefix('/{survey}/questions')
                ->name('questions.')
                ->whereNumber('survey')
                ->group(function () {
                    Route::get('/', [\App\Http\Controllers\QuestionController::class, 'index'])->name('index');
                    Route::get('/create', [\App\Http\Controllers\QuestionController::class, 'create'])->name('create');
                    Route::post('/', [\App\Http\Controllers\QuestionController::class, 'store'])->name('store');
                    Route::get('/{question}/edit', [\App\Http\Controllers\QuestionController::class, 'edit'])
                        ->whereNumber('question')->name('edit');
                    Route::put('/{question}', [\App\Http\Controllers\QuestionController::class, 'update'])
                        ->whereNumber('question')->name('update');
                    Route::delete('/{question}', [\App\Http\Controllers\QuestionController::class, 'destroy'])
                        ->whereNumber('question')->name('destroy');
                    Route::patch('/{question}/move/{direction}', [\App\Http\Controllers\QuestionController::class, 'move'])
                        ->whereNumber('question')
                        ->whereIn('direction', ['up', 'down'])
                        ->name('move');
                });
        });

    Route::prefix('isi-survei')
        ->name('responses.')
        ->middleware(['role:student,teacher'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\SurveyResponseController::class, 'index'])->name('index');
            Route::get('/{survey}', [\App\Http\Controllers\SurveyResponseController::class, 'show'])
                ->whereNumber('survey')->name('show');
            Route::post('/{survey}', [\App\Http\Controllers\SurveyResponseController::class, 'store'])
                ->whereNumber('survey')->name('store');
        });

    Route::prefix('admin/survey-categories')
        ->name('admin.survey-categories.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SurveyCategoryController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\SurveyCategoryController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\SurveyCategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [\App\Http\Controllers\Admin\SurveyCategoryController::class, 'edit'])
                ->whereNumber('category')->name('edit');
            Route::put('/{category}', [\App\Http\Controllers\Admin\SurveyCategoryController::class, 'update'])
                ->whereNumber('category')->name('update');
            Route::delete('/{category}', [\App\Http\Controllers\Admin\SurveyCategoryController::class, 'destroy'])
                ->whereNumber('category')->name('destroy');
        });
});
