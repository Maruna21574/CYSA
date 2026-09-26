<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Quiz;
use App\Models\User;

/**
 * A quiz is managed by its author and the admin of its school; students see published quizzes
 * of courses available to them.
 */
class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTeach();
    }

    public function view(User $user, Quiz $quiz): bool
    {
        if ($this->manages($user, $quiz)) {
            return true;
        }

        return $user->role === UserRole::Student && Quiz::availableTo($user)->whereKey($quiz->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->canTeach() && $user->school_id !== null;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->manages($user, $quiz);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->manages($user, $quiz);
    }

    /**
     * Results, statistics and exports of the quiz.
     */
    public function viewResults(User $user, Quiz $quiz): bool
    {
        return $this->manages($user, $quiz);
    }

    private function manages(User $user, Quiz $quiz): bool
    {
        if ($quiz->school_id !== $user->school_id) {
            return false;
        }

        return $user->role === UserRole::SchoolAdmin
            || ($user->role === UserRole::Teacher && $quiz->author_id === $user->id);
    }
}
