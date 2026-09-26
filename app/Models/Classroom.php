<?php

namespace App\Models;

use App\Enums\ClassroomRole;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'grade_level', 'school_year'])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
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
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->members()->wherePivot('role', ClassroomRole::Student->value);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function teachers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', ClassroomRole::Teacher->value);
    }

    /**
     * Classrooms of the user's school; a super admin sees all of them.
     *
     * @param  Builder<Classroom>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        }
    }

    /**
     * School year in the "2026/2027" format; a new year starts in September.
     */
    public static function currentSchoolYear(): string
    {
        $start = now()->month >= 9 ? now()->year : now()->year - 1;

        return $start.'/'.($start + 1);
    }
}
