<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

/**
 * Schools are managed only by the super admin, who passes every check through Gate::before.
 */
class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, School $school): bool
    {
        return false;
    }

    public function delete(User $user, School $school): bool
    {
        return false;
    }
}
