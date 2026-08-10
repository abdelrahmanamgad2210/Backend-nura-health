<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Services\SafetySignalService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssessmentController extends Controller
{
    public function __construct(private SafetySignalService $safetySignals)
    {
    }

    /**
     * The intake questionnaire is answerable without an account — identity
     * verification is its own step inside the quiz, not a login wall. A
     * guest's in-progress assessment is tracked by a long-lived, httpOnly
     * `guest_assessment_token` cookie instead of `user_id`, same pattern as
     * CartController. `complete()` still requires a real login: it creates a
     * ClinicalCase, which needs an actual patient of record.
     */
    private function ownerColumn(Request $request): array
    {
        if ($request->user()) {
            return ['column' => 'user_id', 'value' => $request->user()->id];
        }

        $token = $request->cookie('guest_assessment_token');

        return ['column' => 'guest_token', 'value' => $token, 'isNewGuest' => ! $token];
    }

    private function attachGuestCookieIfNeeded($response, array $owner, ?string &$newToken = null)
    {
        if (($owner['isNewGuest'] ?? false) && $newToken) {
            $response->cookie('guest_assessment_token', $newToken, 60 * 24 * 365, null, null, false, true, false, 'lax');
        }

        return $response;
    }

    private function authorizeOwner(Request $request, Assessment $assessment): void
    {
        $owner = $this->ownerColumn($request);

        abort_unless(
            $owner['value'] && $assessment->{$owner['column']} === $owner['value'],
            403
        );
    }

    public function index(Request $request)
    {
        return response()->json([
            'assessments' => $request->user()->assessments()
                ->with('clinicalCase', 'intentProduct')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'string'],
            'intent_product_id' => ['nullable', 'exists:products,id'],
        ]);

        $owner = $this->ownerColumn($request);
        $newToken = null;

        if (! $owner['value']) {
            $newToken = (string) Str::uuid();
            $owner['value'] = $newToken;
        }

        $assessment = Assessment::create([
            $owner['column'] => $owner['value'],
            'category' => $data['category'],
            'intent_product_id' => $data['intent_product_id'] ?? null,
            'status' => 'in_progress',
            'current_step' => 1,
            'answers' => [],
        ]);

        $response = response()->json(['assessment' => $assessment], 201);

        return $this->attachGuestCookieIfNeeded($response, $owner, $newToken);
    }

    public function show(Request $request, Assessment $assessment)
    {
        $this->authorizeOwner($request, $assessment);

        return response()->json([
            'assessment' => $assessment->load('clinicalCase.safetySignals', 'intentProduct'),
        ]);
    }

    public function update(Request $request, Assessment $assessment)
    {
        $this->authorizeOwner($request, $assessment);

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
