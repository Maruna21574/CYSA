<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\Format;

class QuizResultNotification extends CysaNotification
{
    public function __construct(public QuizAttempt $attempt) {}

    public function title(): string
    {
        return __('Výsledok testu');
    }

    public function body(): string
    {
        return __('Test „:title“: :percent – :state.', [
            'title' => $this->attempt->quiz->title,
            'percent' => Format::percent($this->attempt->percentage),
            'state' => $this->attempt->passed ? __('úspešne') : __('neúspešne'),
        ]);
    }

    public function url(): string
    {
        return route('attempts.show', $this->attempt);
    }

    public function icon(): string
    {
        return 'chart';
    }

    /**
     * Results are shown in the app only - no e-mail for every quiz.
     *
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database'];
    }
}
