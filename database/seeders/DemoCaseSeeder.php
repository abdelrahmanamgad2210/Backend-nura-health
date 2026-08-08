<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\ClinicalCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SafetySignal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds one already-completed assessment + clinical case (with an order pending
 * clinician review) so a reviewer can exercise the doctor decision flow immediately
 * without first running the full 9-step quiz.
 */
class DemoCaseSeeder extends Seeder
{
    public function run(): void
    {
        $patient = User::where('email', 'patient@nura.demo')->first();
        $clinician = User::where('email', 'clinician@nura.demo')->first();
        $product = Product::where('slug', 'weight-care')->first();

        if (! $patient || ! $clinician || ! $product) {
            return;
        }

        $assessment = Assessment::updateOrCreate(
            ['user_id' => $patient->id, 'category' => 'Weight management', 'intent_product_id' => $product->id],
            [
                'status' => 'completed',
                'current_step' => 9,
                'urgent_flag' => false,
                'answers' => [
                    'goal' => 'Lose weight safely',
                    'age' => 34,
                    'sex' => 'Female',
                    'height_cm' => 164,
                    'weight_kg' => 86,
                    'conditions' => ['Prediabetes or diabetes'],
                    'medicines' => 'Metformin 500 mg; vitamin D',
                    'allergies' => 'No known drug allergies',
                    'urgent' => 'none',
                    'preferences' => ['Home lab collection', 'Care team messaging'],
                ],
                'completed_at' => now()->subMinutes(18),
            ]
        );

        $case = ClinicalCase::updateOrCreate(
            ['assessment_id' => $assessment->id],
            [
                'patient_id' => $patient->id,
                'category' => 'Weight management',
                'risk_flag' => 'amber',
                'ai_draft_summary' => 'Assessment appears suitable for clinician review, but treatment should not be selected until recent HbA1c, renal function, blood pressure, pregnancy status, and eating-disorder screen are confirmed. The amber priority comes from cardiometabolic history plus missing recent laboratory values.',
                'status' => 'new',
                'assigned_clinician_id' => $clinician->id,
            ]
        );

        SafetySignal::query()->where('clinical_case_id', $case->id)->delete();
        SafetySignal::insert([
            ['clinical_case_id' => $case->id, 'label' => 'Recent metabolic labs missing. Obtain clinician-selected labs before treatment decision.', 'severity' => 'amber', 'reference' => 'WM-3.2 §4.1', 'created_at' => now(), 'updated_at' => now()],
            ['clinical_case_id' => $case->id, 'label' => 'Prediabetes + elevated BMI. Review cardiometabolic risk and baseline measurements.', 'severity' => 'amber', 'reference' => 'Q12 + Q7', 'created_at' => now(), 'updated_at' => now()],
            ['clinical_case_id' => $case->id, 'label' => 'No immediate emergency symptom reported. Emergency screen answered "none of these".', 'severity' => 'green', 'reference' => 'Q23', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $order = Order::firstOrCreate(
            ['user_id' => $patient->id, 'status' => 'pending'],
            ['total_due_now' => $product->due_now, 'currency' => 'AED', 'placed_at' => now()->subMinutes(17)]
        );

        OrderItem::updateOrCreate(
            ['order_id' => $order->id, 'product_id' => $product->id],
            [
                'product_slug' => $product->slug,
                'product_name' => $product->name,
                'product_type' => $product->type,
                'unit_price' => $product->price,
                'due_now_amount' => $product->due_now,
                'clinical_case_id' => $case->id,
                'fulfilment_status' => 'pending',
                'authorized_at' => now()->subMinutes(17),
                'captured_at' => null,
            ]
        );
    }
}
