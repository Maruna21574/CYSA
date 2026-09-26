<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    /**
     * The student sees own attempts; the teacher / school admin sees attempts of their quizzes.
     */
    public function view(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id || $user->can('viewResults', $attempt->quiz);
    }

    /**
     * Only the owner may work on an attempt, and only while it is open.
     */
    public function take(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id && $attempt->isInProgress();
    }
}
