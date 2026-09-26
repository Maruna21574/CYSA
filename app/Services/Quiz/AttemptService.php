<?php

namespace App\Services\Quiz;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Events\QuizAttemptRegraded;
use App\Events\QuizAttemptSubmitted;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Progress\ProgressService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Life cycle of a quiz attempt: start (with question snapshots), saving answers, submitting.
 * All timing is decided on the server; a submitted attempt can never be changed by the student.
 */
class AttemptService
{
    /** Tolerance for network latency when the timer runs out. */
    public const GRACE_SECONDS = 30;

    public function __construct(private AnswerGrader $grader, private ProgressService $progress) {}

    public function availability(Quiz $quiz, User $user): QuizAvailability
    {
        $quiz->loadMissing('chapter');
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $user->id)->get();
        $inProgress = $attempts->first(fn (QuizAttempt $attempt): bool => $attempt->isInProgress());
        $used = $attempts->count();
        $left = $quiz->max_attempts !== null ? max(0, $quiz->max_attempts - $used) : null;

        $reason = match (true) {
            $inProgress !== null => null,
            ! $quiz->isPublished() => __('Test nie je zverejnený.'),
            $quiz->available_from !== null && now()->lessThan($quiz->available_from) => __('Test bude dostupný od :date.', ['date' => $quiz->available_from->translatedFormat('j. n. Y H:i')]),
            $quiz->due_at !== null && now()->greaterThan($quiz->due_at) => __('Termín na vypracovanie testu uplynul.'),
            $left === 0 => __('Využil(a) si všetky pokusy.'),
            $quiz->chapter !== null && ! $this->progress->isUnlocked($user, $quiz->chapter) => __('Najprv dokonči predchádzajúcu kapitolu.'),
            default => null,
        };

        return new QuizAvailability($reason === null, $reason, $used, $left, $inProgress);
    }

    /**
     * Starts a new attempt, or returns the one already in progress.
     *
     * @throws QuizUnavailableException
     */
    public function start(Quiz $quiz, User $user): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $user): QuizAttempt {
            // Lock the student's attempts of this quiz: two clicks / two tabs cannot start two attempts.
            $existing = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $user->id)->lockForUpdate()->get();

            if ($inProgress = $existing->first(fn (QuizAttempt $attempt): bool => $attempt->isInProgress())) {
                return $inProgress;
            }

            $availability = $this->availability($quiz, $user);

            if (! $availability->canStart) {
                throw new QuizUnavailableException((string) $availability->reason);
            }

            $questions = $quiz->questions()->with('options')->get();

            if ($questions->isEmpty()) {
                throw new QuizUnavailableException(__('Test zatiaľ neobsahuje otázky.'));
            }

            if ($quiz->shuffle_questions) {
                $questions = $questions->shuffle();
            }

            $startedAt = now();

            $attempt = new QuizAttempt;
            $attempt->forceFill([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'attempt_number' => ((int) $existing->max('attempt_number')) + 1,
                'status' => AttemptStatus::InProgress,
                'started_at' => $startedAt,
                'expires_at' => $this->expiresAt($quiz, $startedAt),
                'question_order' => $questions->pluck('id')->all(),
            ])->save();

            foreach ($questions as $question) {
                $answer = new QuizAnswer;
                $answer->forceFill([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_snapshot' => $this->snapshot($question, (bool) $quiz->shuffle_options),
                    'max_points' => Quiz::pointsFor($question),
                ])->save();
            }

            return $attempt;
        });
    }

    /**
     * @throws QuizUnavailableException when the attempt is closed or the time is up
     */
    public function saveAnswer(QuizAttempt $attempt, int $questionId, mixed $input): void
    {
        $attempt->refresh();

        if (! $attempt->isInProgress()) {
            throw new QuizUnavailableException(__('Test už bol odovzdaný.'));
        }

        if ($attempt->expires_at !== null && now()->greaterThan($attempt->expires_at->copy()->addSeconds(self::GRACE_SECONDS))) {
            throw new QuizUnavailableException(__('Čas na test vypršal.'));
        }

        $answer = $attempt->answers()->where('question_id', $questionId)->firstOrFail();

        $answer->forceFill([
            'response' => $this->grader->normalize($answer->question_snapshot, $input),
            'answered_at' => now(),
        ])->save();
    }

    /**
     * Grades and closes the attempt. Idempotent: submitting twice has no further effect.
     */
    public function submit(QuizAttempt $attempt, bool $timedOut = false): QuizAttempt
    {
        $submitted = DB::transaction(function () use ($attempt, $timedOut): ?QuizAttempt {
            $attempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (! $attempt->isInProgress()) {
                return null;
            }

            $score = 0.0;
            $maxScore = 0.0;

            foreach ($attempt->answers as $answer) {
                $fraction = $this->grader->grade($answer->question_snapshot, $answer->response);
                $points = round($fraction * (float) $answer->max_points, 2);

                $answer->forceFill([
                    'points_awarded' => $points,
                    'is_correct' => $answer->response === null ? false : $fraction >= 0.9999,
                ])->save();

                $this->recordSelectedOptions($answer);

                $score += $points;
                $maxScore += (float) $answer->max_points;
            }

            $finishedAt = $attempt->expires_at !== null && now()->greaterThan($attempt->expires_at) ? $attempt->expires_at : now();
            $percentage = $maxScore > 0 ? round($score / $maxScore * 100, 2) : 0.0;

            $attempt->forceFill([
                'status' => AttemptStatus::Completed,
                'finished_at' => $finishedAt,
                'timed_out' => $timedOut || ($attempt->expires_at !== null && now()->greaterThanOrEqualTo($attempt->expires_at)),
                'time_spent_seconds' => max(0, (int) $attempt->started_at->diffInSeconds($finishedAt)),
                'score' => $score,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passed' => $percentage >= $attempt->quiz->pass_percentage,
            ])->save();

            return $attempt;
        });

        if ($submitted === null) {
            return $attempt->refresh();
        }

        QuizAttemptSubmitted::dispatch($submitted);

        return $submitted;
    }

    /**
     * Manual correction of one answer by the teacher (e.g. an acceptable short answer the
     * automatic grading did not know). Recalculates the attempt totals.
     */
    public function overridePoints(QuizAnswer $answer, float $points): QuizAttempt
    {
        $attempt = DB::transaction(function () use ($answer, $points): QuizAttempt {
            $attempt = QuizAttempt::whereKey($answer->quiz_attempt_id)->lockForUpdate()->firstOrFail();
            $points = round(max(0, min($points, (float) $answer->max_points)), 2);

            $answer->forceFill([
                'points_awarded' => $points,
                'is_correct' => $points >= (float) $answer->max_points,
            ])->save();

            $score = (float) $attempt->answers()->sum('points_awarded');
            $percentage = (float) $attempt->max_score > 0 ? round($score / (float) $attempt->max_score * 100, 2) : 0.0;

            $attempt->forceFill([
                'score' => $score,
                'percentage' => $percentage,
                'passed' => $percentage >= $attempt->quiz->pass_percentage,
            ])->save();

            return $attempt;
        });

        QuizAttemptRegraded::dispatch($attempt);

        return $attempt;
    }

    /**
     * Auto-submits attempts whose time ran out while the student was away.
     */
    public function expireOverdue(): int
    {
        $count = 0;

        QuizAttempt::where('status', AttemptStatus::InProgress)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subSeconds(self::GRACE_SECONDS))
            ->each(function (QuizAttempt $attempt) use (&$count): void {
                $this->submit($attempt, timedOut: true);
                $count++;
            });

        return $count;
    }

    /**
     * Frozen copy of the question used for the whole attempt.
     *
     * @return array<string, mixed>
     */
    public function snapshot(Question $question, bool $shuffleOptions): array
    {
        $options = $question->options->map(fn ($option): array => [
            'id' => $option->id,
            'body' => $option->body,
            'match_body' => $option->match_body,
            'blank_index' => $option->blank_index,
            'is_correct' => $option->is_correct,
        ]);

        if ($shuffleOptions && in_array($question->type, [QuestionType::SingleChoice, QuestionType::MultipleChoice], true)) {
            $options = $options->shuffle();
        }

        return [
            'type' => $question->type->value,
            'body' => $question->body,
            'explanation' => $question->explanation,
            'has_image' => $question->image_path !== null,
            'settings' => $question->settings ?? [],
            'options' => $options->values()->all(),
            // Right-hand items of a matching question are always shown in random order.
            'right_order' => $question->type === QuestionType::Matching ? $options->pluck('id')->shuffle()->values()->all() : [],
        ];
    }

    /**
     * The time limit, but never later than the quiz deadline.
     */
    private function expiresAt(Quiz $quiz, Carbon $startedAt): ?Carbon
    {
        $byLimit = $quiz->time_limit_minutes ? $startedAt->copy()->addMinutes($quiz->time_limit_minutes) : null;

        if ($quiz->due_at === null) {
            return $byLimit;
        }

        return $byLimit === null || $quiz->due_at->lessThan($byLimit) ? $quiz->due_at->copy() : $byLimit;
    }

    private function recordSelectedOptions(QuizAnswer $answer): void
    {
        $type = QuestionType::from($answer->question_snapshot['type']);

        if (! $type->isChoice()) {
            return;
        }

        // Options deleted since the attempt started are skipped (the snapshot still has them).
        $answer->selectedOptions()->sync(
            QuestionOption::whereIn('id', $answer->response['selected'] ?? [])->pluck('id')->all(),
        );
    }
}
