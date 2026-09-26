<?php

namespace App\Enums;

enum QuestionSource: string
{
    case Manual = 'manual';
    case Ai = 'ai';

    public function label(): string
    {
        return match ($this) {
            self::Manual => __('Ručne'),
            self::Ai => __('Návrh AI'),
        };
    }
}
