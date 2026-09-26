<?php

namespace App\Models;

use App\Enums\QuizPurpose;
use App\Enums\QuizStatus;
use App\Enums\ResultVisibility;
use App\Enums\UserRole;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'course_id', 'chapter_id', 'title', 'description', 'purpose', 'paired_quiz_id', 'pass_percentage',
    'max_attempts', 'time_limit_minutes', 'available_from', 'due_at', 'shuffle_questions', 'shuffle_options',
    'show_result', 'show_correct_answers',
])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => 'draft',
        'purpose' => 'practice',
        'pass_percentage' => 60,
        'show_result' => 'immediately',
        'show_correct_answers' => 'immediately',
        'shuffle_questions' => false,
        'shuffle_options' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => QuizPurpose::class,
            'status' => QuizStatus::class,
            'show_result' => ResultVisibility::class,
            'show_correct_answers' => ResultVisibility::class,
            'pass_percentage' => 'integer',
            'max_attempts' => 'integer',
            'time_limit_minutes' => 'integer',
            'available_from' => 'datetime',
            'due_at' => 'datetime',
            'published_at' => 'datetime',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * The matching pre-test / post-test of a research pair.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function pairedQuiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'paired_quiz_id');
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'quiz_question')
            ->withPivot(['id', 'position', 'points'])
            ->withTimestamps()
            ->orderByPivot('position')
            ->orderByPivot('id');
    }

    public function isPublished(): bool
    {
        return $this->status === QuizStatus::Published;
    }

    /**
     * Points of a question inside this quiz (the quiz can override the question's default).
     */
    public static function pointsFor(Question $question): float
    {
        return (float) ($question->pivot?->points ?? $question->default_points);
    }

    public function totalPoints(): float
    {
        return (float) $this->questions->sum(fn (Question $question): float => static::pointsFor($question));
    }

    /**
     * @param  Builder<Quiz>  $query
     */
    public function scopeManageableBy(Builder $query, User $user): void
    {
        match ($user->role) {
            UserRole::SuperAdmin => null,
            UserRole::SchoolAdmin => $query->where('school_id', $user->school_id),
            UserRole::Teacher => $query->where('author_id', $user->id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Published quizzes of courses available to the student.
     *
     * @param  Builder<Quiz>  $query
     */
    public function scopeAvailableTo(Builder $query, User $student): void
    {
        $query->where('status', QuizStatus::Published)
            ->whereHas('course', fn (Builder $query) => $query->availableTo($student));
    }
}
