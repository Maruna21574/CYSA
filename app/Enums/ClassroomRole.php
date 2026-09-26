<?php

namespace App\Enums;

/**
 * Role of a member inside a classroom (classroom_user.role).
 */
enum ClassroomRole: string
{
    case Teacher = 'teacher';
    case Student = 'student';

    public static function forUser(UserRole $role): self
    {
        return $role->canTeach() ? self::Teacher : self::Student;
    }
}
