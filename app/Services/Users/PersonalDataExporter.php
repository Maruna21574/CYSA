<?php

namespace App\Services\Users;

use App\Models\Certificate;
use App\Models\ChapterProgress;
use App\Models\CourseProgress;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * GDPR right of access / portability: everything the platform stores about one user,
 * as a machine readable JSON document.
 */
class PersonalDataExporter
{
    /**
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        // Always export the complete, current record from the database.
        $user = $user->fresh();

        return [
            'generated_at' => now()->toIso8601String(),
            'account' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role->value,
                'school' => $user->school?->name,
                'is_active' => $user->is_active,
                'email_notifications' => $user->email_notifications,
                'research_code' => $user->research_code,
                'created_at' => $user->created_at?->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ],
            'classrooms' => $user->classrooms()->get()->map(fn ($classroom): array => [
                'name' => $classroom->name,
                'school_year' => $classroom->school_year,
                'role' => $classroom->pivot->role,
            ])->all(),
            'course_progress' => CourseProgress::where('user_id', $user->id)->with('course:id,title')->get()->map(fn ($progress): array => [
                'course' => $progress->course?->title,
                'percentage' => (float) $progress->percentage,
                'completed_at' => $progress->completed_at?->toIso8601String(),
            ])->all(),
            'chapter_progress' => ChapterProgress::where('user_id', $user->id)->with('chapter:id,title')->get()->map(fn ($progress): array => [
                'chapter' => $progress->chapter?->title,
                'started_at' => $progress->started_at?->toIso8601String(),
                'completed_at' => $progress->completed_at?->toIso8601String(),
            ])->all(),
            'quiz_attempts' => QuizAttempt::where('user_id', $user->id)->with(['quiz:id,title', 'answers'])->get()->map(fn (QuizAttempt $attempt): array => [
                'quiz' => $attempt->quiz?->title,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'finished_at' => $attempt->finished_at?->toIso8601String(),
                'score' => (float) $attempt->score,
                'max_score' => (float) $attempt->max_score,
                'percentage' => (float) $attempt->percentage,
                'passed' => $attempt->passed,
                'answers' => $attempt->answers->map(fn ($answer): array => [
                    'question' => $answer->question_snapshot['body'] ?? null,
                    'response' => $answer->response,
                    'points' => (float) $answer->points_awarded,
                ])->all(),
            ])->all(),
            'certificates' => Certificate::where('user_id', $user->id)->get(['code', 'course_title', 'issued_at', 'revoked_at'])->toArray(),
            'gamification' => [
                'stats' => DB::table('user_stats')->where('user_id', $user->id)->first(),
                'badges' => DB::table('user_badges')->join('badges', 'badges.id', '=', 'user_badges.badge_id')->where('user_id', $user->id)->pluck('badges.name')->all(),
            ],
            'notifications' => $user->notifications()->get(['data', 'read_at', 'created_at'])->toArray(),
            'security_log' => DB::table('audit_logs')->where('user_id', $user->id)->latest('id')->limit(500)->get(['action', 'ip_address', 'created_at'])->all(),
        ];
    }
}
