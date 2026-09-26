<?php

namespace App\Events;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * All published chapters of the course are completed.
 */
class CourseCompleted
{
    use Dispatchable;

    public function __construct(public User $user, public Course $course) {}
}
