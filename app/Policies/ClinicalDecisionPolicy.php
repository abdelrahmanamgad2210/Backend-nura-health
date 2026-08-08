<?php

namespace App\Policies;

use App\Models\ClinicalDecision;
use App\Models\User;

class ClinicalDecisionPolicy
{
    public function create(User $user): bool
    {
        return $user->isClinician();
    }

    public function view(User $user, ClinicalDecision $decision): bool
    {
        return $user->isClinician() || $user->id === $decision->clinicalCase->patient_id;
    }
}
