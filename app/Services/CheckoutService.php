<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Turns a patient's cart into an Order. This is where the prototype's central
     * commerce rule becomes real, enforced state instead of a UI label:
     *
     *  - direct/service items: full amount is authorised AND captured immediately.
     *  - clinical items: only the review fee (due_now) is authorised; capture never
     *    happens here. A clinical item cannot even reach checkout unless the patient
     *    has a completed assessment matching the product's quiz_category.
     */
    public function checkout(User $user): Order
    {
        $cartItems = $user->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages(['cart' => ['Your cart is empty.']]);
        }

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;

            if ($product->type !== 'clinical') {
                continue;
            }

            $hasCompletedAssessment = $user->assessments()
                ->where('category', $product->quiz_category)
                ->where('status', 'completed')
                ->exists();

            if (! $hasCompletedAssessment) {
                throw ValidationException::withMessages([
                    'cart' => ["Complete the assessment for \"{$product->name}\" before checkout."],
                ]);
            }
        }

        $totalDueNow = $cartItems->sum(fn ($item) => (float) $item->product->due_now);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'processing',
            'total_due_now' => $totalDueNow,
            'currency' => 'AED',
            'placed_at' => now(),
        ]);

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            $isClinical = $product->type === 'clinical';

            $clinicalCaseId = null;
            if ($isClinical && $cartItem->assessment_id) {
                $clinicalCaseId = optional($cartItem->assessment->clinicalCase)->id;
            }
            if ($isClinical && ! $clinicalCaseId) {
                $latestCase = $user->assessments()
                    ->where('category', $product->quiz_category)
                    ->where('status', 'completed')
                    ->latest('completed_at')
                    ->first()?->clinicalCase;
                $clinicalCaseId = $latestCase?->id;
            }

            $order->items()->create([
                'product_id' => $product->id,
                'product_slug' => $product->slug,
                'product_name' => $product->name,
                'product_type' => $product->type,
                'unit_price' => $product->price ?? $product->due_now,
                'due_now_amount' => $product->due_now,
                'clinical_case_id' => $clinicalCaseId,
                'fulfilment_status' => 'pending',
                'authorized_at' => now(),
                'captured_at' => $isClinical ? null : now(),
            ]);
        }

        $user->cartItems()->delete();

        return $order->load('items');
    }
}
