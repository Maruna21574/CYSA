<?php

namespace App\Listeners;

use App\Events\ChapterCompleted;
use App\Events\CourseCompleted;
use App\Events\QuizAttemptRegraded;
use App\Events\QuizAttemptSubmitted;
use App\Models\Course;
use App\Models\User;
use App\Services\Certificates\CertificateService;

/**
 * Re-checks the certificate conditions whenever the student's progress or results change.
 */
class IssueCertificates
{
    public function __construct(private CertificateService $certificates) {}

    public function handleAttemptSubmitted(QuizAttemptSubmitted $event): void
    {
        $this->check($event->attempt->user, $event->attempt->quiz->course);
    }

    public function handleAttemptRegraded(QuizAttemptRegraded $event): void
    {
        $this->check($event->attempt->user, $event->attempt->quiz->course);
    }

    public function handleChapterCompleted(ChapterCompleted $event): void
    {
        $this->check($event->user, $event->chapter->course);
    }

    public function handleCourseCompleted(CourseCompleted $event): void
    {
        $this->check($event->user, $event->course);
    }

    private function check(?User $user, ?Course $course): void
    {
        if ($user && $course && $course->certificate_enabled) {
            $this->certificates->issueIfEligible($user, $course);
        }
    }
}
