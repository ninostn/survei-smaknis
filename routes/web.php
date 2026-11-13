<?php

use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SurveyCategoryController;
use App\Http\Controllers\Admin\TeachingAssignmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyResponseController;
use App\Http\Controllers\SurveyResultController;
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

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')->name('admin.dashboard');

    Route::get('/teacher/dashboard', fn () => view('teacher.dashboard'))
        ->middleware('role:teacher')->name('teacher.dashboard');

    Route::get('/student/dashboard', fn () => view('student.dashboard'))
        ->middleware('role:student')->name('student.dashboard');

    Route::prefix('admin/users')
        ->name('admin.users.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/teachers', [UserController::class, 'storeTeacher'])->name('teachers.store');
            Route::post('/students', [UserController::class, 'storeStudent'])->name('students.store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])
                ->whereNumber('user')->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])
                ->whereNumber('user')->name('update');
            Route::patch('/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->whereNumber('user')->name('reset-password');
        });

    Route::prefix('admin/classes')
        ->name('admin.classes.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [SchoolClassController::class, 'index'])->name('index');
            Route::get('/create', [SchoolClassController::class, 'create'])->name('create');
            Route::post('/', [SchoolClassController::class, 'store'])->name('store');
            Route::get('/{class}/edit', [SchoolClassController::class, 'edit'])
                ->whereNumber('class')->name('edit');
            Route::put('/{class}', [SchoolClassController::class, 'update'])
                ->whereNumber('class')->name('update');
            Route::patch('/{class}/toggle', [SchoolClassController::class, 'toggle'])
                ->whereNumber('class')->name('toggle');
        });

    Route::prefix('admin/subjects')
        ->name('admin.subjects.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [SubjectController::class, 'index'])->name('index');
            Route::get('/create', [SubjectController::class, 'create'])->name('create');
            Route::post('/', [SubjectController::class, 'store'])->name('store');
            Route::get('/{subject}/edit', [SubjectController::class, 'edit'])
                ->whereNumber('subject')->name('edit');
            Route::put('/{subject}', [SubjectController::class, 'update'])
                ->whereNumber('subject')->name('update');
        });

    Route::prefix('admin/teaching-assignments')
        ->name('admin.teaching-assignments.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [TeachingAssignmentController::class, 'index'])->name('index');
            Route::get('/create', [TeachingAssignmentController::class, 'create'])->name('create');
            Route::post('/', [TeachingAssignmentController::class, 'store'])->name('store');
            Route::get('/{assignment}/edit', [TeachingAssignmentController::class, 'edit'])
                ->whereNumber('assignment')->name('edit');
            Route::put('/{assignment}', [TeachingAssignmentController::class, 'update'])
                ->whereNumber('assignment')->name('update');
            Route::delete('/{assignment}', [TeachingAssignmentController::class, 'destroy'])
                ->whereNumber('assignment')->name('destroy');
        });

    Route::prefix('surveys')
        ->name('surveys.')
        ->middleware(['role:admin,teacher'])
        ->group(function () {
            Route::get('/', [SurveyController::class, 'index'])->name('index');
            Route::get('/create', [SurveyController::class, 'create'])->name('create');
            Route::post('/', [SurveyController::class, 'store'])->name('store');
            Route::get('/{survey}/edit', [SurveyController::class, 'edit'])
                ->whereNumber('survey')->name('edit');
            Route::put('/{survey}', [SurveyController::class, 'update'])
                ->whereNumber('survey')->name('update');
            Route::delete('/{survey}', [SurveyController::class, 'destroy'])
                ->whereNumber('survey')->name('destroy');

            Route::get('/{survey}/publish', [SurveyController::class, 'publishForm'])
                ->whereNumber('survey')->name('publish.create');
            Route::patch('/{survey}/publish', [SurveyController::class, 'publish'])
                ->whereNumber('survey')->name('publish');
            Route::patch('/{survey}/close', [SurveyController::class, 'close'])
                ->whereNumber('survey')->name('close');

            Route::get('/{survey}/results', [SurveyResultController::class, 'show'])
                ->whereNumber('survey')->name('results.show');

            Route::patch('/{survey}/results/publication', [SurveyResultController::class, 'updatePublication'])
                ->whereNumber('survey')->name('results.publication.update');

            Route::prefix('/{survey}/questions')
                ->name('questions.')
                ->whereNumber('survey')
                ->group(function () {
                    Route::get('/', [QuestionController::class, 'index'])->name('index');
                    Route::get('/create', [QuestionController::class, 'create'])->name('create');
                    Route::post('/', [QuestionController::class, 'store'])->name('store');
                    Route::get('/{question}/edit', [QuestionController::class, 'edit'])
                        ->whereNumber('question')->name('edit');
                    Route::put('/{question}', [QuestionController::class, 'update'])
                        ->whereNumber('question')->name('update');
                    Route::delete('/{question}', [QuestionController::class, 'destroy'])
                        ->whereNumber('question')->name('destroy');
                    Route::patch('/{question}/move/{direction}', [QuestionController::class, 'move'])
                        ->whereNumber('question')
                        ->whereIn('direction', ['up', 'down'])
                        ->name('move');
                });
        });

    Route::prefix('isi-survei')
        ->name('responses.')
        ->middleware(['role:student'])
        ->group(function () {
            Route::get('/', [SurveyResponseController::class, 'index'])->name('index');
            Route::get('/{survey}', [SurveyResponseController::class, 'show'])
                ->whereNumber('survey')->name('show');
            Route::post('/{survey}', [SurveyResponseController::class, 'store'])
                ->whereNumber('survey')->name('store');
            Route::get('/{survey}/hasil', [SurveyResultController::class, 'showForRespondent'])
                ->whereNumber('survey')->name('results');
        });

    Route::prefix('admin/survey-categories')
        ->name('admin.survey-categories.')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/', [SurveyCategoryController::class, 'index'])->name('index');
            Route::get('/create', [SurveyCategoryController::class, 'create'])->name('create');
            Route::post('/', [SurveyCategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [SurveyCategoryController::class, 'edit'])
                ->whereNumber('category')->name('edit');
            Route::put('/{category}', [SurveyCategoryController::class, 'update'])
                ->whereNumber('category')->name('update');
            Route::delete('/{category}', [SurveyCategoryController::class, 'destroy'])
                ->whereNumber('category')->name('destroy');
        });
});
