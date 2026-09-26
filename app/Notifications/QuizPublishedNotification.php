<?php

namespace App\Notifications;

use App\Models\Quiz;

class QuizPublishedNotification extends CysaNotification
{
    public function __construct(public Quiz $quiz) {}

    public function title(): string
    {
        return __('Nový test');
    }

    public function body(): string
    {
        return $this->quiz->due_at
            ? __('V kurze „:course“ je nový test „:title“ s termínom :date.', [
                'course' => $this->quiz->course->title,
                'title' => $this->quiz->title,
                'date' => $this->quiz->due_at->translatedFormat('j. n. Y H:i'),
            ])
            : __('V kurze „:course“ je nový test „:title“.', ['course' => $this->quiz->course->title, 'title' => $this->quiz->title]);
    }

    public function url(): string
    {
        return route('student.quizzes.show', $this->quiz);
    }

    public function icon(): string
    {
        return 'shield';
    }
}
