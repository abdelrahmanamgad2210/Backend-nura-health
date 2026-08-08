<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPharmacist();
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $user->isPharmacist();
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->isPharmacist();
    }
}
