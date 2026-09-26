<?php

namespace App\Services\Quiz;

use App\Models\QuizAttempt;

/**
 * Whether a student may start (or resume) a quiz right now, and why not.
 */
final readonly class QuizAvailability
{
    public function __construct(
        public bool $canStart,
        public ?string $reason,
        public int $attemptsUsed,
        public ?int $attemptsLeft,
        public ?QuizAttempt $inProgress = null,
    ) {}
}
