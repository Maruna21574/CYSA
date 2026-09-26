<?php

namespace App\Services\Analytics;

use App\Enums\AttemptStatus;
use App\Enums\QuizPurpose;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pre-test / post-test comparison for the thesis research. Only the FIRST completed attempt
 * of each student counts, so repeated attempts do not distort the measured improvement.
 * Students are identified by their pseudonymous research code, never by name.
 */
class ResearchService
{
    /**
     * Pre-tests with a paired post-test that the user manages.
     *
     * @return Collection<int, Quiz>
     */
    public function pairs(User $user): Collection
    {
        return Quiz::manageableBy($user)
            ->where('purpose', QuizPurpose::PreTest)
            ->whereNotNull('paired_quiz_id')
            ->whereHas('pairedQuiz', fn ($query) => $query->where('purpose', QuizPurpose::PostTest))
            ->with(['pairedQuiz', 'course:id,title'])
            ->orderBy('title')
            ->get();
    }

    /**
     * @return array{
     *     students: Collection<int, object>,
     *     stats: array<string, float|int|null>,
     *     topics: Collection<int, object>,
     *     groups: Collection<int, object>
     * }
     */
    public function comparison(Quiz $pre, Quiz $post, ?int $classroomId = null): array
    {
        $preAttempts = $this->firstAttempts($pre, $classroomId);
        $postAttempts = $this->firstAttempts($post, $classroomId);
        $groups = $this->groupLabels($preAttempts->keys()->merge($postAttempts->keys())->unique()->all());

        $students = $preAttempts->keys()
            ->intersect($postAttempts->keys())
            ->map(fn (int $userId): object => (object) [
                'user_id' => $userId,
                'code' => $groups[$userId]->code ?? '',
                'group' => $groups[$userId]->classroom ?? '—',
                'pre' => (float) $preAttempts[$userId]->percentage,
                'post' => (float) $postAttempts[$userId]->percentage,
                'delta' => round((float) $postAttempts[$userId]->percentage - (float) $preAttempts[$userId]->percentage, 2),
            ])
            ->sortBy('code')
            ->values();

        return [
            'students' => $students,
            'stats' => $this->statistics($students, $preAttempts->count(), $postAttempts->count()),
            'topics' => $this->topicComparison($preAttempts->pluck('id')->all(), $postAttempts->pluck('id')->all()),
            'groups' => $students->groupBy('group')->map(fn (Collection $rows, string $group): object => (object) [
                'group' => $group,
                'n' => $rows->count(),
                'pre' => round($rows->avg('pre'), 1),
                'post' => round($rows->avg('post'), 1),
                'delta' => round($rows->avg('delta'), 1),
            ])->sortKeys()->values(),
        ];
    }

    /**
     * One row per answer of the first pre/post attempts - long format for R / SPSS / Python.
     *
     * @return \Generator<int, list<scalar|null>>
     */
    public function exportRows(Quiz $pre, Quiz $post, ?int $classroomId = null): \Generator
    {
        $attempts = $this->firstAttempts($pre, $classroomId)->merge($this->firstAttempts($post, $classroomId))->values();
        $groups = $this->groupLabels($attempts->pluck('user_id')->unique()->all());
        $topics = DB::table('question_topic')
            ->join('topics', 'topics.id', '=', 'question_topic.topic_id')
            ->get(['question_topic.question_id', 'topics.slug'])
            ->groupBy('question_id')
            ->map(fn (Collection $rows): string => $rows->pluck('slug')->implode('|'));

        foreach ($attempts->chunk(200) as $chunk) {
            $answers = DB::table('quiz_answers')->whereIn('quiz_attempt_id', $chunk->pluck('id'))->get()->groupBy('quiz_attempt_id');

            foreach ($chunk as $attempt) {
                $group = $groups[$attempt->user_id] ?? null;

                foreach ($answers[$attempt->id] ?? [] as $answer) {
                    yield [
                        $group?->code,
                        $group?->school_type,
                        $group?->grade,
                        $group?->classroom,
                        $attempt->quiz_id === $pre->id ? 'pre' : 'post',
                        $attempt->quiz_id,
                        $attempt->finished_at?->toDateString(),
                        $attempt->time_spent_seconds,
                        (float) $attempt->percentage,
                        $answer->question_id,
                        json_decode($answer->question_snapshot, true)['type'] ?? null,
                        $topics[$answer->question_id] ?? '',
                        $answer->response === null ? 0 : 1,
                        (int) $answer->is_correct,
                        (float) $answer->points_awarded,
                        (float) $answer->max_points,
                    ];
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    public static function exportHeader(): array
    {
        return [
            'research_code', 'school_type', 'grade_level', 'classroom', 'phase', 'quiz_id', 'finished_on',
            'attempt_seconds', 'attempt_percentage', 'question_id', 'question_type', 'topics', 'answered',
            'is_correct', 'points', 'max_points',
        ];
    }

    /**
     * First completed attempt per student.
     *
     * @return Collection<int, QuizAttempt> keyed by user ID
     */
    private function firstAttempts(Quiz $quiz, ?int $classroomId): Collection
    {
        return QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', AttemptStatus::Completed)
            ->when($classroomId, fn ($query, int $id) => $query->whereIn(
                'user_id',
                DB::table('classroom_user')->where('classroom_id', $id)->where('role', 'student')->select('user_id'),
            ))
            ->orderBy('attempt_number')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');
    }

    /**
     * Pseudonym and grouping variables per student (first classroom, grade, school type).
     *
     * @param  list<int>  $userIds
     * @return Collection<int, object{code: string, classroom: string|null, grade: int|null, school_type: string|null}>
     */
    private function groupLabels(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        $classrooms = DB::table('classroom_user')
            ->join('classrooms', 'classrooms.id', '=', 'classroom_user.classroom_id')
            ->whereIn('classroom_user.user_id', $userIds)
            ->where('classroom_user.role', 'student')
            ->orderBy('classrooms.id')
            ->get(['classroom_user.user_id', 'classrooms.name', 'classrooms.grade_level'])
            ->unique('user_id')
            ->keyBy('user_id');

        return DB::table('users')
            ->leftJoin('schools', 'schools.id', '=', 'users.school_id')
            ->whereIn('users.id', $userIds)
            ->get(['users.id', 'users.research_code', 'schools.type'])
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => (object) [
                'code' => $row->research_code,
                'classroom' => $classrooms[$row->id]->name ?? null,
                'grade' => isset($classrooms[$row->id]) ? $classrooms[$row->id]->grade_level : null,
                'school_type' => $row->type,
            ]]);
    }

    /**
     * Descriptive statistics and a paired t-test of the improvement.
     *
     * @param  Collection<int, object>  $students
     * @return array<string, float|int|null>
     */
    private function statistics(Collection $students, int $preCount, int $postCount): array
    {
        $n = $students->count();
        $deltas = $students->pluck('delta');
        $meanDelta = $n > 0 ? $deltas->avg() : null;
        $sd = $n > 1 ? sqrt($deltas->sum(fn (float $d): float => ($d - $meanDelta) ** 2) / ($n - 1)) : null;

        return [
            'pre_participants' => $preCount,
            'post_participants' => $postCount,
            'paired' => $n,
            'mean_pre' => $n > 0 ? round($students->avg('pre'), 2) : null,
            'mean_post' => $n > 0 ? round($students->avg('post'), 2) : null,
            'mean_delta' => $meanDelta !== null ? round($meanDelta, 2) : null,
            'sd_delta' => $sd !== null ? round($sd, 2) : null,
            'improved' => $students->where('delta', '>', 0)->count(),
            // Paired t statistic (df = n - 1) and Cohen's d for paired samples.
            't' => $sd ? round($meanDelta / ($sd / sqrt($n)), 3) : null,
            'df' => $n > 1 ? $n - 1 : null,
            'cohens_d' => $sd ? round($meanDelta / $sd, 3) : null,
        ];
    }

    /**
     * @param  list<int>  $preAttemptIds
     * @param  list<int>  $postAttemptIds
     * @return Collection<int, object{topic: string, pre: float|null, post: float|null, delta: float|null}>
     */
    private function topicComparison(array $preAttemptIds, array $postAttemptIds): Collection
    {
        $rates = fn (array $attemptIds): Collection => DB::table('quiz_answers')
            ->join('question_topic', 'question_topic.question_id', '=', 'quiz_answers.question_id')
            ->join('topics', 'topics.id', '=', 'question_topic.topic_id')
            ->whereIn('quiz_answers.quiz_attempt_id', $attemptIds ?: [0])
            ->groupBy('topics.name')
            ->select('topics.name')
            ->selectRaw('100.0 * SUM(quiz_answers.points_awarded) / NULLIF(SUM(quiz_answers.max_points), 0) AS success')
            ->pluck('success', 'name');

        $pre = $rates($preAttemptIds);
        $post = $rates($postAttemptIds);

        return $pre->keys()->merge($post->keys())->unique()->sort()->values()
            ->map(fn (string $topic): object => (object) [
                'topic' => $topic,
                'pre' => isset($pre[$topic]) ? round((float) $pre[$topic], 1) : null,
                'post' => isset($post[$topic]) ? round((float) $post[$topic], 1) : null,
                'delta' => isset($pre[$topic], $post[$topic]) ? round((float) $post[$topic] - (float) $pre[$topic], 1) : null,
            ])
            ->sortBy('pre')
            ->values();
    }
}
