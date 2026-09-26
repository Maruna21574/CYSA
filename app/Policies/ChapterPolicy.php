<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\User;

/**
 * Chapter access follows the course. Unpublished chapters are visible only to course managers.
 */
class ChapterPolicy
{
    public function view(User $user, Chapter $chapter): bool
    {
        if ($user->can('update', $chapter->course)) {
            return true;
        }

        return $chapter->is_published && $user->can('view', $chapter->course);
    }

    public function update(User $user, Chapter $chapter): bool
    {
        return $user->can('update', $chapter->course);
    }

    public function delete(User $user, Chapter $chapter): bool
    {
        return $user->can('update', $chapter->course);
    }
}
