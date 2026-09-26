<?php

namespace App\Console\Commands;

use App\Enums\AttemptStatus;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Notifications\DeadlineApproachingNotification;
use App\Services\Notifications\CourseNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('notifications:deadlines {--hours=24 : Upozorniť na testy s termínom v najbližších N hodinách}')]
#[Description('Upozorní študentov na blížiaci sa termín testu, ktorý ešte neodovzdali')]
class SendDeadlineReminders extends Command
{
    public function handle(CourseNotifier $notifier): int
    {
        $sent = 0;

        $quizzes = Quiz::where('status', QuizStatus::Published)
            ->whereBetween('due_at', [now(), now()->addHours((int) $this->option('hours'))])
            ->with('course')
            ->get();

        foreach ($quizzes as $quiz) {
            if (! $quiz->course?->isPublished()) {
                continue;
            }

            $finished = QuizAttempt::where('quiz_id', $quiz->id)->where('status', AttemptStatus::Completed)->pluck('user_id');

            $notifier->students($quiz->course)->whereNotIn('id', $finished)->each(function ($student) use ($quiz, &$sent): void {
                // Once per student and quiz, even though the command runs every hour.
                if (Cache::add("deadline-reminder:{$quiz->id}:{$student->id}", true, now()->addDays(3))) {
                    $student->notify(new DeadlineApproachingNotification($quiz));
                    $sent++;
                }
            });
        }

        $this->info("Odoslané pripomienky: {$sent}");

        return self::SUCCESS;
    }
}
