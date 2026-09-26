<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\Difficulty;
use App\Enums\UserRole;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable(['category_id', 'title', 'description', 'difficulty', 'sequential_chapters'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => 'draft',
        'difficulty' => 'beginner',
    ];

    protected static function booted(): void
    {
        static::creating(function (Course $course): void {
            $course->slug ??= static::uniqueSlug($course->title, $course->school_id);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'difficulty' => Difficulty::class,
            'sequential_chapters' => 'boolean',
            'certificate_enabled' => 'boolean',
            'certificate_min_percentage' => 'integer',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Chapter, $this>
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * @return HasMany<CourseAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    /**
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * @return HasMany<CertificateRequirement, $this>
     */
    public function certificateRequirements(): HasMany
    {
        return $this->hasMany(CertificateRequirement::class);
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * @return HasMany<Announcement, $this>
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    /**
     * Chapters in reading order (module order, then chapter order).
     *
     * @return Collection<int, Chapter>
     */
    public function orderedChapters(bool $publishedOnly = false): Collection
    {
        $modules = $this->modules()->with(['chapters' => fn ($query) => $query
            ->when($publishedOnly, fn ($query) => $query->where('is_published', true)),
        ])->get();

        return $modules->flatMap(fn (Module $module) => $module->chapters)->values();
    }

    /**
     * Courses the user manages: all for a super admin, the school's courses for a school admin,
     * own courses for a teacher.
     *
     * @param  Builder<Course>  $query
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
     * Published courses assigned to the student directly or through one of their classrooms.
     *
     * @param  Builder<Course>  $query
     */
    public function scopeAvailableTo(Builder $query, User $student): void
    {
        $query->where('status', CourseStatus::Published)
            ->where('school_id', $student->school_id)
            ->whereHas('assignments', fn (Builder $query) => $query->activeFor($student));
    }

    public static function uniqueSlug(string $title, ?int $schoolId, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'kurz';
        $slug = $base;
        $counter = 2;

        while (static::withTrashed()
            ->where('school_id', $schoolId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
