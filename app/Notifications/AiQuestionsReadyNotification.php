<?php

namespace App\Notifications;

use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\User;

class AiQuestionsReadyNotification extends CysaNotification
{
    public function __construct(public AiGeneration $generation) {}

    public function title(): string
    {
        return $this->generation->status === AiGenerationStatus::Completed
            ? __('Návrhy otázok sú pripravené')
            : __('Generovanie otázok zlyhalo');
    }

    public function body(): string
    {
        return $this->generation->status === AiGenerationStatus::Completed
            ? __('AI navrhla :n otázok z „:source“. Skontrolujte ich a schváľte.', ['n' => $this->generation->created_questions, 'source' => $this->generation->sourceLabel()])
            : (string) $this->generation->error;
    }

    public function url(): string
    {
        return route('teacher.ai.show', $this->generation);
    }

    public function icon(): string
    {
        return 'sparkles';
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database'];
    }
}
