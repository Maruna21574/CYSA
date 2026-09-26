<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttemptStatus;
use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $since = now()->subDays(13)->startOfDay();

        $attemptsPerDay = QuizAttempt::where('status', AttemptStatus::Completed)
            ->where('finished_at', '>=', $since)
            ->pluck('finished_at')
            ->countBy(fn (Carbon $date): string => $date->toDateString());

        return view('admin.dashboard', [
            'schools' => School::count(),
            'activeSchools' => School::where('is_active', true)->count(),
            'usersByRole' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->label() => User::where('role', $role)->count()]),
            'activeUsers' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'courses' => Course::count(),
            'quizzes' => Quiz::count(),
            'attempts' => QuizAttempt::where('status', AttemptStatus::Completed)->count(),
            'certificates' => Schema::hasTable('certificates') ? DB::table('certificates')->count() : 0,
            'activity' => collect(range(0, 13))->mapWithKeys(fn (int $day): array => [
                $since->copy()->addDays($day)->toDateString() => $attemptsPerDay[$since->copy()->addDays($day)->toDateString()] ?? 0,
            ]),
            'securityEvents' => AuditLog::whereIn('action', [
                AuditAction::LoginFailed->value, AuditAction::Lockout->value, AuditAction::AccountBlocked->value,
                AuditAction::UserRoleChanged->value, AuditAction::AttemptScoreChanged->value,
            ])->with('user:id,first_name,last_name')->latest('id')->limit(10)->get(),
        ]);
    }
}
