<?php

namespace App\Notifications;

use App\Models\Course;

class CourseAssignedNotification extends CysaNotification
{
    public function __construct(public Course $course) {}

    public function title(): string
    {
        return __('Nový kurz');
    }

    public function body(): string
    {
        return __('Bol ti priradený kurz „:title“.', ['title' => $this->course->title]);
    }

    public function url(): string
    {
        return route('courses.show', $this->course);
    }

    public function icon(): string
    {
        return 'book';
    }
}
