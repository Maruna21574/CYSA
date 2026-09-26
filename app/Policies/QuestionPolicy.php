<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;

/**
 * Question bank: a teacher manages own questions, a school admin all questions of the school.
 * Students may only see a question (its image) that is part of a quiz available to them.
 */
class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTeach();
    }

    public function view(User $user, Question $question): bool
    {
        if ($this->manages($user, $question)) {
            return true;
        }

        return $user->role === UserRole::Student
            && Quiz::availableTo($user)->whereHas('questions', fn ($query) => $query->whereKey($question->id))->exists();
    }

    public function create(User $user): bool
    {
        return $user->canTeach() && $user->school_id !== null;
    }

    public function update(User $user, Question $question): bool
    {
        return $this->manages($user, $question);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->manages($user, $question);
    }

    private function manages(User $user, Question $question): bool
    {
        if ($question->school_id !== $user->school_id) {
            return false;
        }

        return $user->role === UserRole::SchoolAdmin
            || ($user->role === UserRole::Teacher && $question->author_id === $user->id);
    }
}
