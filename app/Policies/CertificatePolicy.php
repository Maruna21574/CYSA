<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /**
     * The holder and the managers of the course may open / download the certificate.
     */
    public function view(User $user, Certificate $certificate): bool
    {
        return $certificate->user_id === $user->id || $user->can('update', $certificate->course);
    }

    public function revoke(User $user, Certificate $certificate): bool
    {
        return $user->can('update', $certificate->course);
    }
}
