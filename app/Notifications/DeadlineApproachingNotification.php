<?php

namespace App\Notifications;

use App\Models\Quiz;

class DeadlineApproachingNotification extends CysaNotification
{
    public function __construct(public Quiz $quiz) {}

    public function title(): string
    {
        return __('Blíži sa termín testu');
    }

    public function body(): string
    {
        return __('Test „:title“ treba odovzdať do :date.', [
            'title' => $this->quiz->title,
            'date' => $this->quiz->due_at?->translatedFormat('j. n. Y H:i'),
        ]);
    }

    public function url(): string
    {
        return route('student.quizzes.show', $this->quiz);
    }

    public function icon(): string
    {
        return 'clock';
    }
}
