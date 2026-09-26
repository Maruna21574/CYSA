<?php

namespace App\Services\Notifications;

use App\Enums\ClassroomRole;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use App\Notifications\CourseAssignedNotification;
use App\Notifications\MaterialAddedNotification;
use App\Notifications\QuizPublishedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Sends course related notifications to the students who actually have access to the course.
 * Nothing is sent while the course (or the chapter / quiz) is not visible to students.
 */
class CourseNotifier
{
    /**
     * Active students of the course: directly assigned or members of an assigned classroom.
     *
     * @return Builder<User>
     */
    public function students(Course $course, ?CourseAssignment $only = null): Builder
    {
        $assignments = $only ? collect([$only]) : $course->assignments()->get();

        return User::query()
            ->where('school_id', $course->school_id)
            ->where('role', UserRole::Student)
            ->active()
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $assignments->pluck('user_id')->filter())
                ->orWhereIn('id', DB::table('classroom_user')
                    ->whereIn('classroom_id', $assignments->pluck('classroom_id')->filter())
                    ->where('role', ClassroomRole::Student->value)
                    ->select('user_id')));
    }

    public function courseAvailable(Course $course, ?CourseAssignment $only = null): void
    {
        if ($course->isPublished()) {
            $this->send($this->students($course, $only)->get(), new CourseAssignedNotification($course));
        }
    }

    public function quizPublished(Quiz $quiz): void
    {
        if ($quiz->isPublished() && $quiz->course->isPublished()) {
            $this->send($this->students($quiz->course)->get(), new QuizPublishedNotification($quiz));
        }
    }

    public function materialAdded(Material $material): void
    {
        $chapter = $material->chapter;

        if ($chapter->is_published && $chapter->course->isPublished()) {
            $this->send($this->students($chapter->course)->get(), new MaterialAddedNotification($material));
        }
    }

    public function announcement(Announcement $announcement): void
    {
        $this->send($this->students($announcement->course)->get(), new AnnouncementNotification($announcement));
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function send(Collection $users, object $notification): void
    {
        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }
}
