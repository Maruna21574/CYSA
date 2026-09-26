<?php

namespace App\Enums;

enum SchoolType: string
{
    case Primary = 'zs';
    case Secondary = 'ss';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Primary => __('Základná škola'),
            self::Secondary => __('Stredná škola'),
            self::Other => __('Iná'),
        };
    }
}
