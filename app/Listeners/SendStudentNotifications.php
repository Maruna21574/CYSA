<?php

namespace App\Listeners;

use App\Events\CertificateIssued;
use App\Events\QuizAttemptSubmitted;
use App\Notifications\CertificateIssuedNotification;
use App\Notifications\QuizResultNotification;

/**
 * Personal notifications of a student (own result, own certificate).
 */
class SendStudentNotifications
{
    public function handleAttemptSubmitted(QuizAttemptSubmitted $event): void
    {
        // Only when the quiz settings let the student see the score right away.
        if ($event->attempt->scoreIsVisible()) {
            $event->attempt->user->notify(new QuizResultNotification($event->attempt));
        }
    }

    public function handleCertificateIssued(CertificateIssued $event): void
    {
        $event->certificate->user->notify(new CertificateIssuedNotification($event->certificate));
    }
}
