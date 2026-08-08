<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicalCase;
use App\Services\DecisionService;
use Illuminate\Http\Request;

class ClinicianDecisionController extends Controller
{
    public function __construct(private DecisionService $decisions)
    {
    }

    public function store(Request $request, ClinicalCase $case)
    {
        $this->authorize('decide', $case);

        $data = $request->validate([
            'outcome' => ['required', 'in:approve_plan,request_investigations,request_more_info,book_video_consult,refer_in_person,emergency_stop'],
            'rationale' => ['required', 'string', 'min:3'],
        ]);

        $decision = $this->decisions->apply($case, $request->user(), $data);

        return response()->json(['decision' => $decision, 'case' => $case->fresh('orderItems')], 201);
    }
}
