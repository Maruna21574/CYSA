<?php

namespace App\Services\Progress;

use App\Events\ChapterCompleted;
use App\Events\CourseCompleted;
use App\Models\Chapter;
use App\Models\ChapterProgress;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Chapter and course progress of students, including the sequential unlocking of chapters.
 */
class ProgressService
{
    /**
     * A chapter is locked while the previous published chapter is not completed and either
     * the course is sequential or the chapter itself requires it. Course managers are never locked.
     */
    public function isUnlocked(User $user, Chapter $chapter): bool
    {
        $course = $chapter->loadMissing('course')->course;

        if ($user->can('update', $course) || ! ($course->sequential_chapters || $chapter->requires_previous)) {
            return true;
        }

        $chapters = $course->orderedChapters(publishedOnly: true);
        $index = $chapters->search(fn (Chapter $item): bool => $item->is($chapter));

        if ($index === false || $index === 0) {
            return true;
        }

        return $this->completedIds($user, $course)->contains($chapters[$index - 1]->id);
    }

    public function markStarted(User $user, Chapter $chapter): void
    {
        ChapterProgress::firstOrCreate(
            ['user_id' => $user->id, 'chapter_id' => $chapter->id],
            ['course_id' => $chapter->course_id, 'started_at' => now()],
        );

        $this->refreshCourse($user, $chapter->loadMissing('course')->course);
    }

    public function markCompleted(User $user, Chapter $chapter): void
    {
        $progress = ChapterProgress::firstOrCreate(
            ['user_id' => $user->id, 'chapter_id' => $chapter->id],
            ['course_id' => $chapter->course_id, 'started_at' => now()],
        );

        if ($progress->completed_at !== null) {
            return;
        }

        $progress->update(['completed_at' => now()]);
        ChapterCompleted::dispatch($user, $chapter);

        $this->refreshCourse($user, $chapter->loadMissing('course')->course);
    }

    /**
     * Recomputes the cached course progress from chapter progress.
     */
    public function refreshCourse(User $user, Course $course): CourseProgress
    {
        $chapterIds = $course->chapters()->where('is_published', true)->pluck('id');
        $completed = $this->completedIds($user, $course)->intersect($chapterIds)->count();
        $total = $chapterIds->count();

        $progress = CourseProgress::firstOrNew(['user_id' => $user->id, 'course_id' => $course->id]);
        $wasCompleted = $progress->completed_at !== null;

        $progress->fill([
            'completed_chapters' => $completed,
            'total_chapters' => $total,
            'percentage' => $total > 0 ? round($completed / $total * 100, 2) : 0,
            'started_at' => $progress->started_at ?? now(),
            'last_activity_at' => now(),
            'completed_at' => $total > 0 && $completed === $total ? ($progress->completed_at ?? now()) : null,
        ])->save();

        if (! $wasCompleted && $progress->completed_at !== null) {
            CourseCompleted::dispatch($user, $course);
        }

        return $progress;
    }

    /**
     * @return Collection<int, int>
     */
    public function completedIds(User $user, Course $course): Collection
    {
        return ChapterProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('completed_at')
            ->pluck('chapter_id');
    }
}
