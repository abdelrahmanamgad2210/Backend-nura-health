<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items')->latest()->get();

        return response()->json(['orders' => $orders]);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load('items.clinicalCase.decisions', 'items.prescription');

        return response()->json(['order' => $order]);
    }

    /**
     * The patient accepting a clinician-approved plan is the moment payment for a
     * clinical item is actually captured (never before). This also creates the
     * prescription record the pharmacy portal works from.
     */
    public function acceptPlan(Request $request, Order $order, OrderItem $orderItem)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($orderItem->order_id === $order->id, 404);
        abort_unless($orderItem->fulfilment_status === 'approved_awaiting_acceptance', 422, 'This item is not awaiting acceptance.');

        $orderItem->update([
            'fulfilment_status' => 'accepted_by_patient',
            'captured_at' => now(),
        ]);

        $orderItem->prescription()->firstOrCreate([], [
            'status' => 'ready_for_review',
        ]);

        return response()->json(['order_item' => $orderItem->fresh('prescription')]);
    }
}
