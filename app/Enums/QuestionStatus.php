<?php

namespace App\Enums;

/**
 * AI-generated questions start as drafts and must be approved by a teacher before use.
 */
enum QuestionStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Na kontrolu'),
            self::Approved => __('Schválená'),
        };
    }
}
