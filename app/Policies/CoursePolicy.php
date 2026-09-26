<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;

/**
 * A course is managed by its author and by the admin of its school. Students may view
 * a course only while it is published and assigned to them.
 */
class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTeach();
    }

    public function view(User $user, Course $course): bool
    {
        if ($this->manages($user, $course)) {
            return true;
        }

        return $user->role === UserRole::Student
            && Course::availableTo($user)->whereKey($course->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->canTeach() && $user->school_id !== null;
    }

    public function update(User $user, Course $course): bool
    {
        return $this->manages($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->manages($user, $course);
    }

    /**
     * Assigning the course to classrooms / students.
     */
    public function assign(User $user, Course $course): bool
    {
        return $this->manages($user, $course);
    }

    private function manages(User $user, Course $course): bool
    {
        if ($course->school_id !== $user->school_id) {
            return false;
        }

        return $user->role === UserRole::SchoolAdmin
            || ($user->role === UserRole::Teacher && $course->author_id === $user->id);
    }
}
