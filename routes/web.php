<?php

use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\School\ClassroomController;
use App\Http\Controllers\School\StudentImportController;
use App\Http\Controllers\UserController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

/*
|--------------------------------------------------------------------------
| Authentication (no public registration - accounts are created by admins)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::livewire('/invitation/{token}', ResetPassword::class)->defaults('invitation', true)->name('invitation.accept');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
    | Account management - super admin (everyone) and school admin (own school).
    | Which records each of them may touch is decided by UserPolicy.
    */
    Route::middleware('role:super_admin,school_admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::post('/users/{user}/invitation', [UserController::class, 'sendInvitation'])
            ->middleware('throttle:10,1')
            ->name('users.invitation');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
        Route::resource('schools', SchoolController::class)->except('show');
    });

    Route::prefix('school')->name('school.')->middleware('role:school_admin')->group(function () {
        Route::view('/', 'school.dashboard')->name('dashboard');
        Route::resource('classrooms', ClassroomController::class);
        Route::get('/students/import', [StudentImportController::class, 'create'])->name('students.import');
        Route::post('/students/import', [StudentImportController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('students.import.store');
    });

    Route::prefix('teacher')->name('teacher.')->middleware('role:teacher,school_admin')->group(function () {
        Route::view('/', 'teacher.dashboard')->name('dashboard');
    });

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::view('/', 'student.dashboard')->name('dashboard');
    });
});
