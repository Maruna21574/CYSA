<?php

namespace App\Notifications;

use App\Models\Badge;
use App\Models\User;

class BadgeEarnedNotification extends CysaNotification
{
    public function __construct(public Badge $badge) {}

    public function title(): string
    {
        return __('Nový odznak: :name', ['name' => $this->badge->name]);
    }

    public function body(): string
    {
        return $this->badge->description;
    }

    public function url(): string
    {
        return route('student.achievements');
    }

    public function icon(): string
    {
        return $this->badge->icon;
    }

    /**
     * Badges are celebrated in the app only.
     *
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database'];
    }
}
