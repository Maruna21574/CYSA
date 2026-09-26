<?php

namespace App\Events;

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ChapterCompleted
{
    use Dispatchable;

    public function __construct(public User $user, public Chapter $chapter) {}
}
