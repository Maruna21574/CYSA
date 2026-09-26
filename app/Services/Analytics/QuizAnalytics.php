<?php

namespace App\Services\Analytics;

use App\Enums\AttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregations over completed attempts of the quizzes the user manages.
 * Plain SQL aggregates that run on MySQL/MariaDB (production) and SQLite (tests).
 */
class QuizAnalytics
{
    /** Average success below this share marks a student / question as problematic. */
    public const STRUGGLING_THRESHOLD = 50;

    /**
     * Completed attempts in the scope of the filter.
     *
     * @return Builder<QuizAttempt>
     */
    public function attempts(AnalyticsFilter $filter): Builder
    {
        return QuizAttempt::query()
            ->where('quiz_attempts.status', AttemptStatus::Completed)
            ->whereIn('quiz_attempts.quiz_id', $this->quizIds($filter))
            ->when($filter->classroomId, fn (Builder $query, int $classroomId) => $query->whereIn(
                'quiz_attempts.user_id',
                DB::table('classroom_user')->where('classroom_id', $classroomId)->where('role', 'student')->select('user_id'),
            ))
            ->when($filter->from, fn (Builder $query, $from) => $query->where('quiz_attempts.finished_at', '>=', $from))
            ->when($filter->to, fn (Builder $query, $to) => $query->where('quiz_attempts.finished_at', '<=', $to));
    }

    /**
     * @return array{students: int, attempts: int, average: float|null, pass_rate: float|null, average_seconds: float|null}
     */
    public function summary(AnalyticsFilter $filter): array
    {
        $row = $this->attempts($filter)
            ->selectRaw('COUNT(DISTINCT quiz_attempts.user_id) AS students')
            ->selectRaw('COUNT(*) AS attempts')
            ->selectRaw('AVG(quiz_attempts.percentage) AS average')
            ->selectRaw('AVG(CASE WHEN quiz_attempts.passed = 1 THEN 100.0 ELSE 0 END) AS pass_rate')
            ->selectRaw('AVG(quiz_attempts.time_spent_seconds) AS average_seconds')
            ->toBase()
            ->first();

        return [
            'students' => (int) $row->students,
            'attempts' => (int) $row->attempts,
            'average' => $row->average !== null ? round((float) $row->average, 1) : null,
            'pass_rate' => $row->pass_rate !== null ? round((float) $row->pass_rate, 1) : null,
            'average_seconds' => $row->average_seconds !== null ? (float) $row->average_seconds : null,
        ];
    }

    /**
     * Success rate per question (share of points earned), hardest first.
     *
     * @return Collection<int, object{question_id: int, body: string, type: string, answers: int, success: float, correct: int}>
     */
    public function questionStats(AnalyticsFilter $filter, string $direction = 'asc', ?int $limit = 10): Collection
    {
        return $this->answers($filter)
            ->join('questions', 'questions.id', '=', 'quiz_answers.question_id')
            ->groupBy('quiz_answers.question_id', 'questions.body', 'questions.type')
            ->select('quiz_answers.question_id', 'questions.body', 'questions.type')
            ->selectRaw('COUNT(*) AS answers')
            ->selectRaw('SUM(CASE WHEN quiz_answers.is_correct = 1 THEN 1 ELSE 0 END) AS correct')
            ->selectRaw('ROUND(100.0 * SUM(quiz_answers.points_awarded) / NULLIF(SUM(quiz_answers.max_points), 0), 1) AS success')
            ->orderBy('success', $direction === 'desc' ? 'desc' : 'asc')
            ->orderBy('quiz_answers.question_id')
            ->when($limit, fn (QueryBuilder $query, int $limit) => $query->limit($limit))
            ->get()
            ->map(fn (object $row): object => (object) [
                'question_id' => (int) $row->question_id,
                'body' => $row->body,
                'type' => $row->type,
                'answers' => (int) $row->answers,
                'correct' => (int) $row->correct,
                'success' => (float) $row->success,
            ]);
    }

    /**
     * Wrong options students picked most often (choice questions).
     *
     * @return Collection<int, object{question_id: int, question: string, option: string, picks: int}>
     */
    public function commonWrongAnswers(AnalyticsFilter $filter, int $limit = 10): Collection
    {
        return $this->answers($filter)
            ->join('quiz_answer_option', 'quiz_answer_option.quiz_answer_id', '=', 'quiz_answers.id')
            ->join('question_options', 'question_options.id', '=', 'quiz_answer_option.question_option_id')
            ->join('questions', 'questions.id', '=', 'quiz_answers.question_id')
            ->where('question_options.is_correct', false)
            ->groupBy('quiz_answers.question_id', 'questions.body', 'question_options.id', 'question_options.body')
            ->select('quiz_answers.question_id', 'questions.body AS question', 'question_options.body AS option')
            ->selectRaw('COUNT(*) AS picks')
            ->orderByDesc('picks')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): object => (object) [
                'question_id' => (int) $row->question_id,
                'question' => $row->question,
                'option' => $row->option,
                'picks' => (int) $row->picks,
            ]);
    }

    /**
     * Success rate per cyber security topic - shows where students struggle most.
     *
     * @return Collection<int, object{topic: string, answers: int, success: float}>
     */
    public function topicStats(AnalyticsFilter $filter): Collection
    {
        return $this->answers($filter)
            ->join('question_topic', 'question_topic.question_id', '=', 'quiz_answers.question_id')
            ->join('topics', 'topics.id', '=', 'question_topic.topic_id')
            ->groupBy('topics.id', 'topics.name')
            ->select('topics.name AS topic')
            ->selectRaw('COUNT(*) AS answers')
            ->selectRaw('ROUND(100.0 * SUM(quiz_answers.points_awarded) / NULLIF(SUM(quiz_answers.max_points), 0), 1) AS success')
            ->orderBy('success')
            ->get()
            ->map(fn (object $row): object => (object) [
                'topic' => $row->topic,
                'answers' => (int) $row->answers,
                'success' => (float) $row->success,
            ]);
    }

    /**
     * Per-student overview, weakest first.
     *
     * @return Collection<int, object{user_id: int, name: string, attempts: int, average: float, passed: int, last_at: string|null, struggling: bool}>
     */
    public function studentStats(AnalyticsFilter $filter, ?int $limit = null): Collection
    {
        return $this->attempts($filter)
            ->join('users', 'users.id', '=', 'quiz_attempts.user_id')
            ->groupBy('quiz_attempts.user_id', 'users.first_name', 'users.last_name')
            ->select('quiz_attempts.user_id', 'users.first_name', 'users.last_name')
            ->selectRaw('COUNT(*) AS attempts')
            ->selectRaw('AVG(quiz_attempts.percentage) AS average')
            ->selectRaw('SUM(CASE WHEN quiz_attempts.passed = 1 THEN 1 ELSE 0 END) AS passed_count')
            ->selectRaw('MAX(quiz_attempts.finished_at) AS last_at')
            ->orderBy('average')
            ->when($limit, fn (Builder $query, int $limit) => $query->limit($limit))
            ->toBase()
            ->get()
            ->map(fn (object $row): object => (object) [
                'user_id' => (int) $row->user_id,
                'name' => trim($row->first_name.' '.$row->last_name),
                'attempts' => (int) $row->attempts,
                'average' => round((float) $row->average, 1),
                'passed' => (int) $row->passed_count,
                'last_at' => $row->last_at,
                'struggling' => (float) $row->average < self::STRUGGLING_THRESHOLD,
            ]);
    }

    /**
     * Answers of the attempts in scope, as a base query for joins.
     */
    private function answers(AnalyticsFilter $filter): QueryBuilder
    {
        return DB::table('quiz_answers')
            ->whereIn('quiz_answers.quiz_attempt_id', $this->attempts($filter)->select('quiz_attempts.id'));
    }

    private function quizIds(AnalyticsFilter $filter): Builder
    {
        return Quiz::withTrashed()
            ->manageableBy($filter->user)
            ->when($filter->courseId, fn (Builder $query, int $courseId) => $query->where('course_id', $courseId))
            ->when($filter->quizId, fn (Builder $query, int $quizId) => $query->whereKey($quizId))
            ->select('id');
    }
}
