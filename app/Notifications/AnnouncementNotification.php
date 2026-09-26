<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Support\Str;

class AnnouncementNotification extends CysaNotification
{
    public function __construct(public Announcement $announcement) {}

    public function title(): string
    {
        return __('Oznámenie: :title', ['title' => $this->announcement->title]);
    }

    public function body(): string
    {
        return Str::limit($this->announcement->body, 300);
    }

    public function url(): string
    {
        return route('courses.show', $this->announcement->course_id);
    }

    public function icon(): string
    {
        return 'bell';
    }
}
