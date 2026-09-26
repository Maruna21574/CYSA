<?php

namespace App\Enums;

/**
 * Why the quiz exists. Pre and post tests are paired and compared in the research analysis.
 */
enum QuizPurpose: string
{
    case Practice = 'practice';
    case Graded = 'graded';
    case PreTest = 'pretest';
    case PostTest = 'posttest';

    public function label(): string
    {
        return match ($this) {
            self::Practice => __('Precvičovací kvíz'),
            self::Graded => __('Test'),
            self::PreTest => __('Vstupný test (pred vzdelávaním)'),
            self::PostTest => __('Výstupný test (po vzdelávaní)'),
        };
    }

    public function isResearch(): bool
    {
        return in_array($this, [self::PreTest, self::PostTest], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
