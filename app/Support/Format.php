<?php

namespace App\Support;

/**
 * Slovak number formatting used in views (decimal comma, no trailing zeros).
 */
class Format
{
    public static function number(float|int|string|null $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $formatted = number_format((float) $value, $decimals, ',', ' ');

        return str_contains($formatted, ',') ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }

    public static function percent(float|int|string|null $value): string
    {
        return $value === null ? '—' : self::number($value, 1).' %';
    }

    public static function duration(float|int|null $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        $seconds = (int) round($seconds);

        return $seconds >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60)
            : sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
