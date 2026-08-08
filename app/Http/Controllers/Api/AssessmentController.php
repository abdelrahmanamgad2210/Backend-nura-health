<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Services\SafetySignalService;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct(private SafetySignalService $safetySignals)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'string'],
            'intent_product_id' => ['nullable', 'exists:products,id'],
        ]);

        $assessment = Assessment::create([
            'user_id' => $request->user()->id,
            'category' => $data['category'],
            'intent_product_id' => $data['intent_product_id'] ?? null,
            'status' => 'in_progress',
            'current_step' => 1,
            'answers' => [],
        ]);

        return response()->json(['assessment' => $assessment], 201);
    }

    public function show(Request $request, Assessment $assessment)
    {
        abort_unless($assessment->user_id === $request->user()->id, 403);

        return response()->json(['assessment' => $assessment]);
    }

    public function update(Request $request, Assessment $assessment)
    {
        abort_unless($assessment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'current_step' => ['sometimes', 'integer', 'min:1', 'max:9'],
            'answers' => ['sometimes', 'array'],
            'urgent_flag' => ['sometimes', 'boolean'],
            'urgent_reason' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('answers', $data)) {
            $data['answers'] = array_merge($assessment->answers ?? [], $data['answers']);
        }

        $assessment->update($data);

        return response()->json(['assessment' => $assessment]);
    }

    public function complete(Request $request, Assessment $assessment)
    {
        abort_unless($assessment->user_id === $request->user()->id, 403);

        $assessment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        if (! $assessment->clinicalCase) {
            $this->safetySignals->buildCase($assessment);
        }

        return response()->json(['assessment' => $assessment->fresh('clinicalCase.safetySignals')]);
    }
}
