<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

/**
 * A material is as visible as its chapter.
 */
class MaterialPolicy
{
    public function view(User $user, Material $material): bool
    {
        return $user->can('view', $material->chapter);
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->can('update', $material->chapter);
    }
}
