<?php

namespace App\Enums;

/**
 * Kind of organization. Schools and companies share the same data model (the "schools"
 * table); a company only uses different wording - departments and employees instead of
 * classrooms and students.
 */
enum SchoolType: string
{
    case Primary = 'zs';
    case Secondary = 'ss';
    case Other = 'other';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Primary => __('Základná škola'),
            self::Secondary => __('Stredná škola'),
            self::Other => __('Iná'),
            self::Company => __('Firma / organizácia'),
        };
    }

    public function isCompany(): bool
    {
        return $this === self::Company;
    }

    /**
     * Types offered when creating a school (everything except a company).
     *
     * @return list<self>
     */
    public static function schoolTypes(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => ! $type->isCompany()));
    }
}
