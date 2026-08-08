<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicalCase;
use Illuminate\Http\Request;

class ClinicianCaseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ClinicalCase::class);

        $cases = ClinicalCase::query()
            ->with('patient', 'safetySignals')
            ->when($request->query('risk'), fn ($query, $risk) => $query->where('risk_flag', $risk))
            ->latest()
            ->get();

        return response()->json(['cases' => $cases]);
    }

    public function show(Request $request, ClinicalCase $case)
    {
        $this->authorize('view', $case);

        $case->load('patient', 'assessment', 'safetySignals', 'decisions.clinician', 'orderItems');

        return response()->json(['case' => $case]);
    }
}
