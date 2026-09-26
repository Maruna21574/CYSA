<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * School admins manage teachers and students of their own school only.
 * The super admin passes every check through Gate::before.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SchoolAdmin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SchoolAdmin;
    }

    public function update(User $user, User $model): bool
    {
        return $this->manages($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->manages($user, $model);
    }

    private function manages(User $user, User $model): bool
    {
        return $user->role === UserRole::SchoolAdmin
            && $model->school_id === $user->school_id
            && in_array($model->role, UserRole::assignableBy($user->role), true);
    }
}
