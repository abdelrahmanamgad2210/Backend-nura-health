<?php

namespace App\Services;

use App\Models\ClinicalCase;
use App\Models\ClinicalDecision;
use App\Models\User;

class DecisionService
{
    /**
     * Records the clinician's decision (an immutable audit row — see
     * ClinicalDecision::booted()) and, only on "approve_plan", flips the case's
     * pending clinical order item(s) to "approved, awaiting patient acceptance".
     * Payment is still not captured here — that only happens when the patient
     * accepts (OrderController::acceptPlan).
     */
    public function apply(ClinicalCase $case, User $clinician, array $data): ClinicalDecision
    {
        $decision = ClinicalDecision::create([
            'clinical_case_id' => $case->id,
            'clinician_id' => $clinician->id,
            'outcome' => $data['outcome'],
            'rationale' => $data['rationale'],
            'esigned_name' => $clinician->name,
            'decided_at' => now(),
        ]);

        $case->update(['status' => 'decided', 'assigned_clinician_id' => $clinician->id]);

        if ($data['outcome'] === 'approve_plan') {
            $case->orderItems()
                ->whereIn('fulfilment_status', ['pending', 'blocked'])
                ->update(['fulfilment_status' => 'approved_awaiting_acceptance']);
        }

        return $decision;
    }
}
