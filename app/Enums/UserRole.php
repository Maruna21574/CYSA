<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case SchoolAdmin = 'school_admin';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super administrátor'),
            self::SchoolAdmin => __('Administrátor školy'),
            self::Teacher => __('Učiteľ'),
            self::Student => __('Študent'),
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
     * Every role except the super admin must belong to a school.
     */
    public function requiresSchool(): bool
    {
        return $this !== self::SuperAdmin;
    }
}
