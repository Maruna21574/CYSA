<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
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
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
    });

    Route::prefix('school')->name('school.')->middleware('role:school_admin')->group(function () {
        Route::view('/', 'school.dashboard')->name('dashboard');
    });

    Route::prefix('teacher')->name('teacher.')->middleware('role:teacher,school_admin')->group(function () {
        Route::view('/', 'teacher.dashboard')->name('dashboard');
    });

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::view('/', 'student.dashboard')->name('dashboard');
    });
});
