<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\AttemptResultController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\QuestionImageController;
use App\Http\Controllers\School;
use App\Http\Controllers\School\ClassroomController;
use App\Http\Controllers\School\StudentImportController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Teacher;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifyCertificateController;
use App\Livewire;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

// Public certificate verification (only validity, holder, course and date are shown).
Route::get('/verify-certificate/{code?}', VerifyCertificateController::class)
    ->middleware('throttle:30,1')
    ->name('certificates.verify');

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
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::resource('schools', SchoolController::class)->except('show');
    });

    Route::prefix('school')->name('school.')->middleware('role:school_admin')->group(function () {
        Route::get('/', School\DashboardController::class)->name('dashboard');
        Route::resource('classrooms', ClassroomController::class);
        Route::get('/students/import', [StudentImportController::class, 'create'])->name('students.import');
        Route::post('/students/import', [StudentImportController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('students.import.store');
    });

    Route::prefix('teacher')->name('teacher.')->middleware('role:teacher,school_admin')->group(function () {
        Route::get('/', Teacher\DashboardController::class)->name('dashboard');

        Route::resource('courses', Teacher\CourseController::class);
        Route::patch('/courses/{course}/status', Teacher\CourseStatusController::class)->name('courses.status');
        Route::get('/courses/{course}/assignments', [Teacher\CourseController::class, 'assignments'])->name('courses.assignments');
        Route::controller(Teacher\ChapterController::class)
            ->prefix('/courses/{course}/chapters')
            ->name('courses.chapters.')
            ->scopeBindings()
            ->group(function () {
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{chapter}/edit', 'edit')->name('edit');
                Route::put('/{chapter}', 'update')->name('update');
                Route::delete('/{chapter}', 'destroy')->name('destroy');
            });

        Route::livewire('/questions', Livewire\Teacher\QuestionBank::class)->name('questions.index');
        Route::livewire('/questions/create', Livewire\Teacher\QuestionEditor::class)->name('questions.create');
        Route::livewire('/questions/{question}/edit', Livewire\Teacher\QuestionEditor::class)->name('questions.edit');

        Route::resource('quizzes', Teacher\QuizController::class);
        Route::patch('/quizzes/{quiz}/status', Teacher\QuizStatusController::class)->name('quizzes.status');
        Route::get('/quizzes/{quiz}/results', [Teacher\QuizResultController::class, 'show'])->name('quizzes.results');
        Route::get('/quizzes/{quiz}/results/export', [Teacher\QuizResultController::class, 'export'])
            ->middleware('throttle:20,1')
            ->name('quizzes.results.export');
        Route::patch('/attempts/{attempt}/answers/{answer}/score', Teacher\AnswerScoreController::class)
            ->scopeBindings()
            ->name('attempts.answers.score');

        Route::get('/courses/{course}/certificate', [Teacher\CourseCertificateController::class, 'edit'])->name('courses.certificate.edit');
        Route::put('/courses/{course}/certificate', [Teacher\CourseCertificateController::class, 'update'])->name('courses.certificate.update');
        Route::post('/certificates/{certificate}/revoke', [CertificateController::class, 'revoke'])->name('certificates.revoke');

        Route::get('/analytics', [Teacher\AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/research', [Teacher\ResearchController::class, 'index'])->name('research.index');
        Route::get('/research/{quiz}/export', [Teacher\ResearchController::class, 'export'])
            ->middleware('throttle:20,1')
            ->name('research.export');
    });

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/', Student\DashboardController::class)->name('dashboard');
        Route::get('/courses', [Student\CourseController::class, 'index'])->name('courses.index');
        Route::post('/courses/{course}/chapters/{chapter}/complete', [Student\ChapterProgressController::class, 'store'])
            ->scopeBindings()
            ->name('chapters.complete');

        Route::get('/quizzes/{quiz}', [Student\QuizController::class, 'show'])->name('quizzes.show');
        Route::post('/quizzes/{quiz}/start', [Student\QuizController::class, 'start'])
            ->middleware('throttle:10,1')
            ->name('quizzes.start');
        Route::livewire('/attempts/{attempt}', Livewire\Student\QuizPlayer::class)->name('attempts.play');
        Route::get('/results', [Student\ResultController::class, 'index'])->name('results.index');
        Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    });

    /*
    | Learning pages and private files, shared by students and teachers (preview).
    | Access is decided by CoursePolicy / ChapterPolicy / MaterialPolicy.
    */
    Route::get('/courses/{course}', [LearningController::class, 'course'])->name('courses.show');
    Route::get('/courses/{course}/chapters/{chapter}', [LearningController::class, 'chapter'])
        ->scopeBindings()
        ->name('chapters.show');
    Route::get('/courses/{course}/cover', [MaterialController::class, 'cover'])->name('courses.cover');
    Route::get('/materials/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::get('/questions/{question}/image', QuestionImageController::class)->name('questions.image');
    Route::get('/attempts/{attempt}', AttemptResultController::class)->name('attempts.show');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
});
