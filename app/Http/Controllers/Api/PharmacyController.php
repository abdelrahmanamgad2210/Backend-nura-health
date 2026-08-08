<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Prescription::class);

        $prescriptions = Prescription::query()
            ->with('orderItem.order.user', 'orderItem.clinicalCase.decisions')
            ->latest()
            ->get();

        return response()->json(['prescriptions' => $prescriptions]);
    }

    public function show(Request $request, Prescription $prescription)
    {
        $this->authorize('view', $prescription);

        $prescription->load('orderItem.order.user', 'orderItem.clinicalCase.decisions');

        return response()->json(['prescription' => $prescription]);
    }

    public function updateChecklist(Request $request, Prescription $prescription)
    {
        $this->authorize('update', $prescription);

        $data = $request->validate([
            'physician_verified' => ['sometimes', 'boolean'],
            'identity_match' => ['sometimes', 'boolean'],
            'duplicate_fill_check' => ['sometimes', 'boolean'],
            'dur_review' => ['sometimes', 'boolean'],
            'counselling_required' => ['sometimes', 'boolean'],
        ]);

        $prescription->update($data);
        $prescription->update(['status' => 'ready_for_review']);

        return response()->json(['prescription' => $prescription]);
    }

    /**
     * Gated on both: the linked clinical decision being an approval, AND every
     * checklist item being complete. Neither condition alone is sufficient.
     */
    public function accept(Request $request, Prescription $prescription)
    {
        $this->authorize('update', $prescription);

        $orderItem = $prescription->orderItem()->with('clinicalCase.decisions')->first();
        $latestDecision = $orderItem?->clinicalCase?->decisions->sortByDesc('decided_at')->first();

        abort_unless($latestDecision && $latestDecision->outcome === 'approve_plan', 422, 'No approved clinical decision on file for this order item.');
        abort_unless($prescription->checklistComplete(), 422, 'Complete all 5 verification checks before accepting.');

        $prescription->update([
            'pharmacist_id' => $request->user()->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $orderItem->update(['fulfilment_status' => 'dispensing']);

        return response()->json(['prescription' => $prescription->fresh()]);
    }
}
