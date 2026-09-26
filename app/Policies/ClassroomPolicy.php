<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;

/**
 * Classrooms are managed by the admin of their school. Teachers may view the classrooms they teach.
 */
class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SchoolAdmin;
    }

    public function view(User $user, Classroom $classroom): bool
    {
        if ($this->isAdminOf($user, $classroom)) {
            return true;
        }

        return $user->canTeach() && $classroom->teachers()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SchoolAdmin;
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $this->isAdminOf($user, $classroom);
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $this->isAdminOf($user, $classroom);
    }

    private function isAdminOf(User $user, Classroom $classroom): bool
    {
        return $user->role === UserRole::SchoolAdmin && $classroom->school_id === $user->school_id;
    }
}
