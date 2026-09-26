<?php

namespace App\Gamification;

use App\Events\CertificateIssued;
use App\Events\ChapterCompleted;
use App\Events\CourseCompleted;
use App\Events\QuizAttemptSubmitted;
use App\Models\User;
use Illuminate\Events\Dispatcher;

/**
 * Connects the gamification module to domain events. Registered in AppServiceProvider -
 * removing that one line switches the whole module off.
 */
class GamificationSubscriber
{
    public function __construct(private GamificationService $gamification) {}

    public function onAttemptSubmitted(QuizAttemptSubmitted $event): void
    {
        $attempt = $event->attempt;

        $this->whenEnabled($attempt->user, function (User $user) use ($attempt): void {
            $this->gamification->recordActivity($user);

            // Points once per quiz (first pass), extra points for a perfect result.
            if ($attempt->passed) {
                $this->gamification->award($user, GamificationService::XP_QUIZ_PASSED + (int) floor((float) $attempt->percentage / 10), 'quiz_passed', $attempt->quiz);
            }

            if ((float) $attempt->percentage >= 100) {
                $this->gamification->award($user, GamificationService::XP_PERFECT, 'quiz_perfect', $attempt->quiz);
            }
        });
    }

    public function onChapterCompleted(ChapterCompleted $event): void
    {
        $this->whenEnabled($event->user, fn (User $user) => $this->gamification->award($user, GamificationService::XP_CHAPTER, 'chapter_completed', $event->chapter));
    }

    public function onCourseCompleted(CourseCompleted $event): void
    {
        $this->whenEnabled($event->user, fn (User $user) => $this->gamification->award($user, GamificationService::XP_COURSE, 'course_completed', $event->course));
    }

    public function onCertificateIssued(CertificateIssued $event): void
    {
        $this->whenEnabled($event->certificate->user, fn (User $user) => $this->gamification->award($user, GamificationService::XP_CERTIFICATE, 'certificate', $event->certificate));
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            QuizAttemptSubmitted::class => 'onAttemptSubmitted',
            ChapterCompleted::class => 'onChapterCompleted',
            CourseCompleted::class => 'onCourseCompleted',
            CertificateIssued::class => 'onCertificateIssued',
        ];
    }

    private function whenEnabled(?User $user, callable $callback): void
    {
        if (! $this->gamification->enabledFor($user)) {
            return;
        }

        $callback($user);
        $this->gamification->checkBadges($user);
    }
}
