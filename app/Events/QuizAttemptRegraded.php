<?php

namespace App\Events;

use App\Models\QuizAttempt;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A teacher changed the points of an already submitted attempt.
 */
class QuizAttemptRegraded
{
    use Dispatchable;

    public function __construct(public QuizAttempt $attempt) {}
}
