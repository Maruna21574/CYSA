<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cached aggregate of chapter_progress, recomputed by ProgressService::refreshCourse().
 */
#[Fillable(['user_id', 'course_id', 'completed_chapters', 'total_chapters', 'percentage', 'started_at', 'completed_at', 'last_activity_at'])]
class CourseProgress extends Model
{
    protected $table = 'course_progress';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
