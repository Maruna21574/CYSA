<?php

namespace App\Enums;

/**
 * When a student sees their score, and (separately) when they see the correct answers.
 */
enum ResultVisibility: string
{
    case Immediately = 'immediately';
    case AfterDue = 'after_due';
    case Never = 'never';

    public function label(): string
    {
        return match ($this) {
            self::Immediately => __('Hneď po odovzdaní'),
            self::AfterDue => __('Až po termíne testu'),
            self::Never => __('Nezobrazovať'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
