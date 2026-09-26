<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['course_id', 'chapter_id', 'type', 'body', 'explanation', 'default_points', 'difficulty', 'settings'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, SoftDeletes;

    /** Placeholder of a blank in fill-in-the-blank questions: [[1]], [[2]], ... */
    public const BLANK_PATTERN = '/\[\[(\d{1,2})\]\]/';

    protected $attributes = [
        'status' => 'approved',
        'source' => 'manual',
        'default_points' => 1,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'difficulty' => Difficulty::class,
            'status' => QuestionStatus::class,
            'source' => QuestionSource::class,
            'settings' => 'array',
            'default_points' => 'decimal:2',
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
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Topic, $this>
     */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsToMany<Quiz, $this>
     */
    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_question')->withPivot(['position', 'points'])->withTimestamps();
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Blank numbers used in the body of a fill-in-the-blank question, in order.
     *
     * @return list<int>
     */
    public static function blankIndexes(string $body): array
    {
        preg_match_all(self::BLANK_PATTERN, $body, $matches);

        return array_values(array_unique(array_map('intval', $matches[1])));
    }

    /**
     * @param  Builder<Question>  $query
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
     * @param  Builder<Question>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', QuestionStatus::Approved);
    }
}
