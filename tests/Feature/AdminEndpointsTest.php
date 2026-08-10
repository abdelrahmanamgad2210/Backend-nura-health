<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClinicalCase;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function patient(): User
    {
        return User::factory()->create(['role' => 'patient']);
    }

    private function product(): Product
    {
        return Product::create([
            'slug' => 'fibre-support', 'type' => 'direct', 'category' => 'Nutrition',
            'name' => 'Daily Fibre', 'short_description' => 's', 'long_description' => 'l',
            'price' => 119, 'due_now' => 119, 'includes' => [], 'flow' => [],
        ]);
    }

    public function test_non_admin_roles_are_forbidden_from_every_admin_route(): void
    {
        $patient = $this->patient();

        $this->actingAs($patient)->getJson('/api/admin/dashboard')->assertForbidden();
        $this->actingAs($patient)->getJson('/api/admin/products')->assertForbidden();
        $this->actingAs($patient)->getJson('/api/admin/users')->assertForbidden();
        $this->actingAs($patient)->getJson('/api/admin/orders')->assertForbidden();
        $this->actingAs($patient)->getJson('/api/admin/cases')->assertForbidden();
    }

    public function test_guest_is_unauthenticated_on_admin_routes(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_admin_can_create_update_and_delete_a_product(): void
    {
        $admin = $this->admin();

        $created = $this->actingAs($admin)->postJson('/api/admin/products', [
            'slug' => 'new-service', 'type' => 'direct', 'category' => 'Wellness',
            'name' => 'New Service', 'short_description' => 'short', 'long_description' => 'long',
            'due_now' => 99, 'includes' => ['a'], 'flow' => ['step 1'],
        ])->assertCreated();

        $productId = $created->json('product.id');

        $this->actingAs($admin)
            ->patchJson("/api/admin/products/{$productId}", [
                'slug' => 'new-service', 'type' => 'direct', 'category' => 'Wellness',
                'name' => 'Updated Name', 'short_description' => 'short', 'long_description' => 'long',
                'due_now' => 149, 'includes' => ['a'], 'flow' => ['step 1'],
            ])
            ->assertOk()
            ->assertJsonPath('product.name', 'Updated Name');

        $this->actingAs($admin)
            ->deleteJson("/api/admin/products/{$productId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    public function test_deleting_a_product_preserves_past_order_item_snapshots(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $product = $this->product();

        $order = Order::create(['user_id' => $patient->id, 'status' => 'processing', 'total_due_now' => 119, 'placed_at' => now()]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_slug' => $product->slug, 'product_name' => $product->name,
            'product_type' => 'direct', 'unit_price' => 119, 'due_now_amount' => 119,
            'fulfilment_status' => 'pending', 'authorized_at' => now(), 'captured_at' => now(),
        ]);

        $this->actingAs($admin)->deleteJson("/api/admin/products/{$product->id}")->assertNoContent();

        $this->assertNull($item->fresh()->product_id);
        $this->assertEquals('Daily Fibre', $item->fresh()->product_name);
    }

    public function test_admin_can_list_users_and_orders_and_cases(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $product = $this->product();

        Order::create(['user_id' => $patient->id, 'status' => 'processing', 'total_due_now' => 119, 'placed_at' => now()]);

        $assessment = Assessment::create([
            'user_id' => $patient->id, 'category' => 'Weight management',
            'status' => 'completed', 'completed_at' => now(), 'answers' => [],
        ]);
        ClinicalCase::create([
            'assessment_id' => $assessment->id, 'patient_id' => $patient->id,
            'category' => 'Weight management', 'risk_flag' => 'amber',
        ]);

        $this->actingAs($admin)->getJson('/api/admin/users')->assertOk()->assertJsonCount(2, 'users');
        $this->actingAs($admin)->getJson('/api/admin/orders')->assertOk()->assertJsonCount(1, 'orders');
        $this->actingAs($admin)->getJson('/api/admin/cases')->assertOk()->assertJsonCount(1, 'cases');
    }

    public function test_admin_dashboard_returns_operations_and_finance_sections(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $product = $this->product();

        $order = Order::create(['user_id' => $patient->id, 'status' => 'processing', 'total_due_now' => 119, 'placed_at' => now()]);
        $order->items()->create([
            'product_id' => $product->id, 'product_slug' => $product->slug, 'product_name' => $product->name,
            'product_type' => 'direct', 'unit_price' => 119, 'due_now_amount' => 119,
            'fulfilment_status' => 'pending', 'authorized_at' => now(), 'captured_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard')->assertOk();

        $response->assertJsonStructure([
            'operations' => ['users_by_role', 'orders_by_status', 'clinical_cases_by_status', 'pending_clinical_reviews', 'pharmacy_queue_length', 'total_assessments', 'urgent_assessments'],
            'finance' => ['captured_revenue', 'pending_authorised_revenue', 'revenue_by_day', 'signups_by_day', 'top_products', 'total_orders', 'total_users'],
        ]);
        $this->assertEquals(119, $response->json('finance.captured_revenue'));
    }
}
