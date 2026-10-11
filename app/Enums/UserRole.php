<?php

namespace App\Enums;

use App\Models\School;
use App\Support\Terms;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case SchoolAdmin = 'school_admin';
    case Teacher = 'teacher';
    case Student = 'student';

    /**
     * Role name in the wording of the organization (a company has trainers and employees).
     * Without an organization the signed-in user's one is used.
     */
    public function label(?School $school = null): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super administrátor'),
            self::SchoolAdmin => Terms::of('Administrátor školy', school: $school),
            self::Teacher => Terms::of('Učiteľ', school: $school),
            self::Student => Terms::of('Študent', school: $school),
        };
    }

    /**
     * Role name covering both schools and companies, for the super admin who works with both.
     */
    public function genericLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super administrátor'),
            self::SchoolAdmin => __('Administrátor školy / firmy'),
            self::Teacher => __('Učiteľ / školiteľ'),
            self::Student => __('Študent / zamestnanec'),
        };
    }

    /**
     * Name of the route the user lands on after login.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::SuperAdmin => 'admin.dashboard',
            self::SchoolAdmin => 'school.dashboard',
            self::Teacher => 'teacher.dashboard',
            self::Student => 'student.dashboard',
        };
    }

    /**
     * School admins also teach, so they get every teacher ability.
     */
    public function canTeach(): bool
    {
        return in_array($this, [self::Teacher, self::SchoolAdmin], true);
    }

    /**
     * Roles the given user may assign when creating or editing accounts.
     *
     * @return list<self>
     */
    public static function assignableBy(UserRole $actor): array
    {
        return match ($actor) {
            self::SuperAdmin => self::cases(),
            self::SchoolAdmin => [self::Teacher, self::Student],
            default => [],
        };
    }

    /**
     * Every role except the super admin must belong to a school.
     */
    public function requiresSchool(): bool
    {
        return $this !== self::SuperAdmin;
    }
}
