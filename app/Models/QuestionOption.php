<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meaning of the columns per question type:
 * - choice types: body = option text, is_correct marks the right ones
 * - short answer: every row is an accepted answer (is_correct = true)
 * - fill in the blank: body = accepted answer for blank number blank_index
 * - matching: body = left item, match_body = its right counterpart
 */
#[Fillable(['body', 'match_body', 'blank_index', 'is_correct', 'position'])]
class QuestionOption extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'blank_index' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
