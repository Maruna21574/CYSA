<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use App\Enums\ResultVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One try of a student at a quiz. Nothing is mass assignable: attempts are created and
 * finished only by App\Services\Quiz\AttemptService.
 */
class QuizAttempt extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'finished_at' => 'datetime',
            'timed_out' => 'boolean',
            'passed' => 'boolean',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'question_order' => 'array',
            'time_spent_seconds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return HasMany<QuizAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === AttemptStatus::InProgress;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThanOrEqualTo($this->expires_at);
    }

    public function secondsRemaining(): ?int
    {
        return $this->expires_at ? max(0, (int) now()->diffInSeconds($this->expires_at, false)) : null;
    }

    /**
     * May the student see the score of this attempt now?
     */
    public function scoreIsVisible(): bool
    {
        return ! $this->isInProgress() && $this->isVisible($this->quiz->show_result);
    }

    /**
     * May the student see which answers were correct (and the explanations) now?
     */
    public function correctAnswersAreVisible(): bool
    {
        return $this->scoreIsVisible() && $this->isVisible($this->quiz->show_correct_answers);
    }

    private function isVisible(ResultVisibility $visibility): bool
    {
        return match ($visibility) {
            ResultVisibility::Immediately => true,
            ResultVisibility::AfterDue => $this->quiz->due_at === null || now()->greaterThan($this->quiz->due_at),
            ResultVisibility::Never => false,
        };
    }
}
