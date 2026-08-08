<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CartItem;
use App\Models\ClinicalCase;
use App\Models\ClinicalDecision;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\SafetySignal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class GoldenPathInvariantsTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): User
    {
        return User::factory()->create(['role' => 'patient']);
    }

    private function clinician(): User
    {
        return User::factory()->create(['role' => 'clinician']);
    }

    private function pharmacist(): User
    {
        return User::factory()->create(['role' => 'pharmacist']);
    }

    private function directProduct(): Product
    {
        return Product::create([
            'slug' => 'fibre-support', 'type' => 'direct', 'category' => 'Nutrition',
            'name' => 'Daily Fibre', 'short_description' => 's', 'long_description' => 'l',
            'price' => 119, 'due_now' => 119, 'includes' => [], 'flow' => [],
        ]);
    }

    private function clinicalProduct(): Product
    {
        return Product::create([
            'slug' => 'weight-care', 'type' => 'clinical', 'category' => 'Weight management',
            'name' => 'Weight Care Pathway', 'short_description' => 's', 'long_description' => 'l',
            'price' => 249, 'due_now' => 149, 'quiz_category' => 'Weight management',
            'includes' => [], 'flow' => [],
        ]);
    }

    public function test_direct_item_checkout_is_captured_immediately(): void
    {
        $patient = $this->patient();
        $product = $this->directProduct();
        $this->actingAs($patient)->postJson('/api/cart/items', ['product_id' => $product->id])->assertCreated();

        $response = $this->actingAs($patient)->postJson('/api/checkout')->assertCreated();

        $item = OrderItem::first();
        $this->assertNotNull($item->authorized_at);
        $this->assertNotNull($item->captured_at);
    }

    public function test_clinical_item_checkout_authorizes_but_does_not_capture(): void
    {
        $patient = $this->patient();
        $product = $this->clinicalProduct();

        $assessment = Assessment::create([
            'user_id' => $patient->id, 'category' => 'Weight management',
            'status' => 'completed', 'completed_at' => now(), 'answers' => [],
        ]);

        $this->actingAs($patient)->postJson('/api/cart/items', ['product_id' => $product->id])->assertCreated();
        $this->actingAs($patient)->postJson('/api/checkout')->assertCreated();

        $item = OrderItem::first();
        $this->assertNotNull($item->authorized_at);
        $this->assertNull($item->captured_at);
    }

    public function test_clinical_checkout_is_blocked_without_a_completed_assessment(): void
    {
        $patient = $this->patient();
        $product = $this->clinicalProduct();

        $this->actingAs($patient)->postJson('/api/cart/items', ['product_id' => $product->id])->assertCreated();

        $this->actingAs($patient)->postJson('/api/checkout')
            ->assertStatus(422)
            ->assertJsonFragment(['cart' => ["Complete the assessment for \"{$product->name}\" before checkout."]]);
    }

    public function test_approve_plan_decision_flips_order_item_to_approved_awaiting_acceptance(): void
    {
        $patient = $this->patient();
        $clinician = $this->clinician();
        $product = $this->clinicalProduct();

        $assessment = Assessment::create([
            'user_id' => $patient->id, 'category' => 'Weight management',
            'status' => 'completed', 'completed_at' => now(), 'answers' => [],
        ]);
        $case = ClinicalCase::create([
            'assessment_id' => $assessment->id, 'patient_id' => $patient->id,
            'category' => 'Weight management', 'risk_flag' => 'amber',
        ]);
        $order = Order::create(['user_id' => $patient->id, 'status' => 'processing', 'total_due_now' => 149, 'placed_at' => now()]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_slug' => $product->slug, 'product_name' => $product->name,
            'product_type' => 'clinical', 'unit_price' => 249, 'due_now_amount' => 149,
            'clinical_case_id' => $case->id, 'fulfilment_status' => 'pending', 'authorized_at' => now(),
        ]);

        $this->actingAs($clinician)
            ->postJson("/api/clinician/cases/{$case->id}/decisions", [
                'outcome' => 'approve_plan', 'rationale' => 'Baseline checks satisfactory.',
            ])
            ->assertCreated();

        $this->assertEquals('approved_awaiting_acceptance', $item->fresh()->fulfilment_status);
    }

    public function test_clinical_decision_is_immutable(): void
    {
        $clinician = $this->clinician();
        $case = ClinicalCase::create([
            'assessment_id' => Assessment::create(['user_id' => $this->patient()->id, 'category' => 'x', 'status' => 'completed', 'answers' => []])->id,
            'patient_id' => $this->patient()->id, 'category' => 'x', 'risk_flag' => 'green',
        ]);
        $decision = ClinicalDecision::create([
            'clinical_case_id' => $case->id, 'clinician_id' => $clinician->id,
            'outcome' => 'request_more_info', 'rationale' => 'Need more info.',
            'esigned_name' => $clinician->name, 'decided_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $decision->update(['rationale' => 'Changed my mind.']);
    }

    public function test_accept_plan_captures_using_snapshotted_price_not_a_later_live_price(): void
    {
        $patient = $this->patient();
        $product = $this->clinicalProduct();
        $order = Order::create(['user_id' => $patient->id, 'status' => 'processing', 'total_due_now' => 149, 'placed_at' => now()]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_slug' => $product->slug, 'product_name' => $product->name,
            'product_type' => 'clinical', 'unit_price' => 249, 'due_now_amount' => 149,
            'fulfilment_status' => 'approved_awaiting_acceptance', 'authorized_at' => now(),
        ]);

        // Live product price changes after the order was placed.
        $product->update(['price' => 999]);

        $this->actingAs($patient)
            ->postJson("/api/orders/{$order->id}/items/{$item->id}/accept-plan")
            ->assertOk();

        $item->refresh();
        $this->assertNotNull($item->captured_at);
        $this->assertEquals('249.00', $item->unit_price);
        $this->assertEquals('accepted_by_patient', $item->fulfilment_status);
    }

    public function test_product_price_change_never_alters_existing_order_items(): void
    {
        $product = $this->directProduct();
        $order = Order::create(['user_id' => $this->patient()->id, 'status' => 'processing', 'total_due_now' => 119, 'placed_at' => now()]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_slug' => $product->slug, 'product_name' => $product->name,
            'product_type' => 'direct', 'unit_price' => 119, 'due_now_amount' => 119,
            'fulfilment_status' => 'fulfilled', 'authorized_at' => now(), 'captured_at' => now(),
        ]);

        $product->update(['price' => 500, 'due_now' => 500, 'name' => 'Renamed Product']);

        $item->refresh();
        $this->assertEquals('119.00', $item->unit_price);
        $this->assertEquals('Daily Fibre', $item->product_name);
    }

    public function test_pharmacy_accept_is_rejected_unless_approved_and_checklist_complete(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patient();
        $clinician = $this->clinician();
        $product = $this->clinicalProduct();

        $assessment = Assessment::create(['user_id' => $patient->id, 'category' => 'Weight management', 'status' => 'completed', 'answers' => []]);
        $case = ClinicalCase::create(['assessment_id' => $assessment->id, 'patient_id' => $patient->id, 'category' => 'Weight management', 'risk_flag' => 'green']);
        $order = Order::create(['user_id' => $patient->id, 'status' => 'processing', 'total_due_now' => 149, 'placed_at' => now()]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_slug' => $product->slug, 'product_name' => $product->name,
            'product_type' => 'clinical', 'unit_price' => 249, 'due_now_amount' => 149,
            'clinical_case_id' => $case->id, 'fulfilment_status' => 'accepted_by_patient',
            'authorized_at' => now(), 'captured_at' => now(),
        ]);
        $prescription = Prescription::create(['order_item_id' => $item->id, 'status' => 'ready_for_review']);

        // No decision on file yet -> rejected even with a full checklist.
        $prescription->update([
            'physician_verified' => true, 'identity_match' => true,
            'duplicate_fill_check' => true, 'dur_review' => true, 'counselling_required' => true,
        ]);
        $this->actingAs($pharmacist)
            ->postJson("/api/pharmacy/prescriptions/{$prescription->id}/accept")
            ->assertStatus(422);

        ClinicalDecision::create([
            'clinical_case_id' => $case->id, 'clinician_id' => $clinician->id,
            'outcome' => 'approve_plan', 'rationale' => 'Approved.',
            'esigned_name' => $clinician->name, 'decided_at' => now(),
        ]);

        // Decision now approved, but checklist incomplete -> still rejected.
        $prescription->update(['physician_verified' => false]);
        $this->actingAs($pharmacist)
            ->postJson("/api/pharmacy/prescriptions/{$prescription->id}/accept")
            ->assertStatus(422);

        // Both satisfied -> accepted.
        $prescription->update(['physician_verified' => true]);
        $this->actingAs($pharmacist)
            ->postJson("/api/pharmacy/prescriptions/{$prescription->id}/accept")
            ->assertOk();

        $this->assertEquals('accepted', $prescription->fresh()->status);
        $this->assertEquals('dispensing', $item->fresh()->fulfilment_status);
    }

    public function test_urgent_screen_answer_sets_urgent_flag_and_creates_red_signal(): void
    {
        $patient = $this->patient();
        $assessment = Assessment::create([
            'user_id' => $patient->id, 'category' => 'Sexual health', 'status' => 'in_progress',
            'urgent_flag' => true, 'urgent_reason' => 'chest pain', 'answers' => ['conditions' => []],
        ]);

        $this->actingAs($patient)
            ->postJson("/api/assessments/{$assessment->id}/complete")
            ->assertOk();

        $case = ClinicalCase::where('assessment_id', $assessment->id)->first();
        $this->assertEquals('red', $case->risk_flag);
        $this->assertTrue(SafetySignal::where('clinical_case_id', $case->id)->where('severity', 'red')->exists());
    }

    public function test_rbac_forbids_pharmacist_from_clinician_decisions_and_patient_from_pharmacy_queue(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patient();
        $case = ClinicalCase::create([
            'assessment_id' => Assessment::create(['user_id' => $patient->id, 'category' => 'x', 'status' => 'completed', 'answers' => []])->id,
            'patient_id' => $patient->id, 'category' => 'x', 'risk_flag' => 'green',
        ]);

        $this->actingAs($pharmacist)
            ->postJson("/api/clinician/cases/{$case->id}/decisions", ['outcome' => 'approve_plan', 'rationale' => 'x'])
            ->assertForbidden();

        $this->actingAs($patient)
            ->getJson('/api/pharmacy/queue')
            ->assertForbidden();
    }

    public function test_guest_can_browse_products_and_add_to_cart_without_authentication(): void
    {
        $product = $this->directProduct();

        $this->getJson('/api/products')->assertOk();
        $this->getJson("/api/products/{$product->slug}")->assertOk();
        $this->getJson('/api/cart')->assertOk()->assertJson(['items' => []]);

        $this->postJson('/api/cart/items', ['product_id' => $product->id])->assertCreated();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'user_id' => null,
        ]);
        $this->assertTrue(CartItem::whereNotNull('guest_token')->exists());
    }

    public function test_guest_cart_merges_into_account_on_login(): void
    {
        $product = $this->directProduct();
        $patient = $this->patient();
        $guestToken = (string) Str::uuid();

        CartItem::create(['guest_token' => $guestToken, 'product_id' => $product->id]);

        $this->withCookie('guest_cart_token', $guestToken)
            ->withCredentials()
            ->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/login', ['email' => $patient->email, 'password' => 'password'])
            ->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'user_id' => $patient->id,
            'guest_token' => null,
        ]);
    }
}
