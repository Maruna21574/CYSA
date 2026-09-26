<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => __('Prebieha'),
            self::Completed => __('Odovzdaný'),
        };
    }
}
