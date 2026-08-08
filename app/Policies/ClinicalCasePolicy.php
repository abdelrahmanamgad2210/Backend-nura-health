<?php

namespace App\Policies;

use App\Models\ClinicalCase;
use App\Models\User;

class ClinicalCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isClinician();
    }

    public function view(User $user, ClinicalCase $case): bool
    {
        return $user->isClinician() || $user->id === $case->patient_id;
    }

    public function decide(User $user, ClinicalCase $case): bool
    {
        return $user->isClinician();
    }
}
