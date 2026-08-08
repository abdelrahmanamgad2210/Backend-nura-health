<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ClinicalCase;
use App\Models\SafetySignal;

/**
 * Hardcoded, deterministic rule set mirroring the prototype's adaptiveReason()/case
 * signal logic (index.html ~line 1750, ~line 1804-1809). No real ML/AI is involved —
 * the "AI-prepared draft" is just an assembled string; the rules are the actual gate.
 */
class SafetySignalService
{
    public function buildCase(Assessment $assessment): ClinicalCase
    {
        $answers = $assessment->answers ?? [];
        $conditions = $answers['conditions'] ?? [];

        $riskFlag = $assessment->urgent_flag ? 'red' : 'green';
        $signals = [];

        if ($assessment->urgent_flag) {
            $signals[] = [
                'label' => 'Urgent safety-screen answer reported. Stop the remote commercial flow; activate the configured emergency workflow.',
                'severity' => 'red',
                'reference' => 'Q23',
            ];
        }

        if (in_array('Prediabetes or diabetes', $conditions, true)) {
            $riskFlag = $riskFlag === 'red' ? 'red' : 'amber';
            $signals[] = [
                'label' => 'Prediabetes or diabetes reported. Review cardiometabolic risk, recent HbA1c, and baseline measurements before any treatment decision.',
                'severity' => 'amber',
                'reference' => 'WM-3.2 §4.1',
            ];
        }

        if (in_array('Pancreatitis', $conditions, true)) {
            $riskFlag = $riskFlag === 'red' ? 'red' : 'amber';
            $signals[] = [
                'label' => 'History of pancreatitis reported. This can materially change treatment eligibility and requires clinician review.',
                'severity' => 'amber',
                'reference' => 'Q12',
            ];
        }

        if (empty($signals)) {
            $signals[] = [
                'label' => 'No immediate emergency symptom reported. Emergency screen answered "none of these".',
                'severity' => 'green',
                'reference' => 'Q23',
            ];
        }

        $case = ClinicalCase::create([
            'assessment_id' => $assessment->id,
            'patient_id' => $assessment->user_id,
            'category' => $assessment->category,
            'risk_flag' => $riskFlag,
            'ai_draft_summary' => $this->draftSummary($assessment, $riskFlag),
            'status' => 'new',
        ]);

        foreach ($signals as $signal) {
            SafetySignal::create([
                'clinical_case_id' => $case->id,
                ...$signal,
            ]);
        }

        return $case;
    }

    private function draftSummary(Assessment $assessment, string $riskFlag): string
    {
        if ($riskFlag === 'red') {
            return 'The emergency-screen answer requires immediate clinician action and in-person evaluation. Do not continue a medicine-selection or checkout path. Confirm the patient received the configured emergency instruction and document the handoff.';
        }

        if ($riskFlag === 'amber') {
            return 'Assessment appears suitable for clinician review, but treatment should not be selected until the flagged history items are addressed and any missing baseline investigations are confirmed.';
        }

        return 'No urgent signal is present. A clinician should review the reported history, preferences, and whether baseline investigation is appropriate before selecting a plan.';
    }
}
