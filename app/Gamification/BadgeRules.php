<?php

namespace App\Gamification;

use App\Enums\AttemptStatus;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Conditions of the individual badges (keyed by badges.key).
 */
class BadgeRules
{
    /** Topic badges need at least this many answers and this success rate. */
    private const TOPIC_MIN_ANSWERS = 5;

    private const TOPIC_MIN_SUCCESS = 90.0;

    public function isMet(string $key, User $user, UserStat $stats): bool
    {
        return match ($key) {
            'first_pass' => $this->attempts($user)->where('passed', true)->exists(),
            'perfect_score' => $this->attempts($user)->where('percentage', '>=', 100)->exists(),
            'phishing_expert' => $this->topicMastered($user, 'phishing'),
            'password_pro' => $this->topicMastered($user, 'hesla-a-autentifikacia'),
            'five_courses' => DB::table('course_progress')->where('user_id', $user->id)->whereNotNull('completed_at')->count() >= 5,
            'streak_7' => $stats->longest_streak >= 7,
            'first_certificate' => DB::table('certificates')->where('user_id', $user->id)->whereNull('revoked_at')->exists(),
            default => false,
        };
    }

    private function attempts(User $user): Builder
    {
        return DB::table('quiz_attempts')->where('user_id', $user->id)->where('status', AttemptStatus::Completed->value);
    }

    private function topicMastered(User $user, string $topicSlug): bool
    {
        $row = DB::table('quiz_answers')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_answers.quiz_attempt_id')
            ->join('question_topic', 'question_topic.question_id', '=', 'quiz_answers.question_id')
            ->join('topics', 'topics.id', '=', 'question_topic.topic_id')
            ->where('quiz_attempts.user_id', $user->id)
            ->where('quiz_attempts.status', AttemptStatus::Completed->value)
            ->where('topics.slug', $topicSlug)
            ->selectRaw('COUNT(*) AS answers')
            ->selectRaw('100.0 * SUM(quiz_answers.points_awarded) / NULLIF(SUM(quiz_answers.max_points), 0) AS success')
            ->first();

        return (int) $row->answers >= self::TOPIC_MIN_ANSWERS && (float) $row->success >= self::TOPIC_MIN_SUCCESS;
    }
}
