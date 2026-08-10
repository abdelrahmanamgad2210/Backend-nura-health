<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicalCase;
use Illuminate\Http\Request;

class ClinicalCaseController extends Controller
{
    public function index(Request $request)
    {
        $cases = ClinicalCase::query()
            ->with(['patient:id,name,email', 'assignedClinician:id,name', 'safetySignals'])
            ->when($request->query('risk'), fn ($query, $risk) => $query->where('risk_flag', $risk))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return response()->json(['cases' => $cases]);
    }
}
