<?php

namespace App\Events;

use App\Models\QuizAttempt;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired once, after an attempt was graded and committed. Listeners: certificates,
 * gamification, notifications.
 */
class QuizAttemptSubmitted
{
    use Dispatchable;

    public function __construct(public QuizAttempt $attempt) {}
}
