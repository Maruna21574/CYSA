<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['classroom_id', 'user_id', 'assigned_by', 'available_from', 'due_at'])]
class CourseAssignment extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_from' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Assignments that currently give the student access (directly or through a classroom).
     *
     * @param  Builder<CourseAssignment>  $query
     */
    public function scopeActiveFor(Builder $query, User $student): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('user_id', $student->id)
            ->orWhereIn('classroom_id', $student->classrooms()->select('classrooms.id')))
            ->where(fn (Builder $query) => $query->whereNull('available_from')->orWhere('available_from', '<=', now()));
    }
}
