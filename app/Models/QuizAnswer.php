<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Answer to one question inside an attempt, together with the frozen question snapshot.
 * Written only by App\Services\Quiz\AttemptService.
 */
class QuizAnswer extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_snapshot' => 'array',
            'response' => 'array',
            'is_correct' => 'boolean',
            'points_awarded' => 'decimal:2',
            'max_points' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }

    /**
     * @return BelongsToMany<QuestionOption, $this>
     */
    public function selectedOptions(): BelongsToMany
    {
        return $this->belongsToMany(QuestionOption::class, 'quiz_answer_option');
    }
}
