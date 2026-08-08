<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'slug' => 'weight-care',
                'type' => 'clinical',
                'category' => 'Weight management',
                'name' => 'Weight Care Pathway',
                'short_description' => 'Assessment, clinician review, care plan and connected follow-up.',
                'long_description' => 'A structured metabolic-care journey that starts with your goals and history. The clinician may recommend lifestyle care, investigations, a consultation, treatment, referral, or no treatment.',
                'price' => 249,
                'due_now' => 149,
                'price_note' => 'AED 149 review fee today · care plan from AED 249/month only if approved',
                'badge' => 'Doctor review required',
                'consult_only' => false,
                'quiz_category' => 'Weight management',
                'includes' => ['Adaptive metabolic assessment', 'Licensed clinician review', 'One video consultation when indicated', 'Care Passport, check-ins and secure messaging'],
                'flow' => ['Choose goal', 'Complete assessment', 'Clinician decides', 'Accept final plan'],
            ],
            [
                'slug' => 'fibre-support',
                'type' => 'direct',
                'category' => 'Appetite + nutrition',
                'name' => 'Daily Fibre + Appetite Support',
                'short_description' => 'A simple nutrition-support routine with clear ingredients and guidance.',
                'long_description' => 'A fictional non-prescription nutrition support product with responsible instructions, ingredient transparency and optional care-team guidance. It is not a substitute for medical assessment.',
                'price' => 119,
                'due_now' => 119,
                'price_note' => 'One-time purchase · 30 servings',
                'badge' => 'Buy now',
                'consult_only' => false,
                'quiz_category' => null,
                'includes' => ['30-serving sealed jar', 'Ingredient and allergen information', 'Nutrition guide', 'Secure product-support messaging'],
                'flow' => ['Add to cart', 'Verify account', 'Vendor prepares', 'Discreet delivery'],
            ],
            [
                'slug' => 'hair-system',
                'type' => 'clinical',
                'category' => 'Hair health',
                'name' => 'Personalised Hair Health System',
                'short_description' => 'Photo-guided assessment, clinician review and a tailored care system.',
                'long_description' => 'A clinician-led pathway for shedding, pattern change and scalp symptoms. The product request never determines the diagnosis or plan; photos, history and investigations may be required.',
                'price' => 289,
                'due_now' => 99,
                'price_note' => 'AED 99 review fee today · products only after approval',
                'badge' => 'Doctor review required',
                'consult_only' => false,
                'quiz_category' => 'Hair health',
                'includes' => ['Guided image capture', 'Hair and scalp assessment', 'Clinician-selected plan', 'Progress photography and follow-up'],
                'flow' => ['Share history', 'Upload photos', 'Clinician review', 'Plan + fulfilment'],
            ],
            [
                'slug' => 'skin-routine',
                'type' => 'direct',
                'category' => 'Skin',
                'name' => 'Sensitive Skin Clarity Routine',
                'short_description' => 'A gentle fictional cleanser, serum and moisturiser system.',
                'long_description' => 'A direct-care routine for patients who want a simple, fragrance-conscious starting point. Persistent, severe, painful or rapidly changing symptoms should enter the clinician review pathway.',
                'price' => 159,
                'due_now' => 159,
                'price_note' => 'One-time purchase · three-piece routine',
                'badge' => 'Buy now',
                'consult_only' => false,
                'quiz_category' => null,
                'includes' => ['Gentle cleanser', 'Barrier-support serum', 'Daily moisturiser', 'Patch-test and escalation guidance'],
                'flow' => ['Review details', 'Add to cart', 'Vendor prepares', 'Track delivery'],
            ],
            [
                'slug' => 'home-lab',
                'type' => 'service',
                'category' => 'Diagnostics',
                'name' => 'Home Metabolic Lab Panel',
                'short_description' => 'Licensed home collection with results routed to your clinician.',
                'long_description' => 'A fictional home diagnostic service covering a clinician-approved metabolic baseline. Exact tests, preparation, eligibility and interpretation are confirmed before collection.',
                'price' => 449,
                'due_now' => 449,
                'price_note' => 'Includes home collection in Dubai',
                'badge' => 'Home service',
                'consult_only' => false,
                'quiz_category' => null,
                'includes' => ['Home collection appointment', 'Identity and order verification', 'Chain of custody', 'Results in your Care Passport'],
                'flow' => ['Choose slot', 'Order verified', 'Home collection', 'Clinician reviews'],
            ],
            [
                'slug' => 'intimate-review',
                'type' => 'clinical',
                'category' => 'Sexual health',
                'name' => 'Private Sexual Health Review',
                'short_description' => 'Discreet assessment with cardiovascular and medicine-safety checks.',
                'long_description' => 'A confidential clinician review for common sexual-health concerns. Urgent symptoms, cardiovascular risk or medicine interactions stop the commercial pathway and prompt the appropriate next step.',
                'price' => 199,
                'due_now' => 120,
                'price_note' => 'AED 120 clinical review fee today',
                'badge' => 'Doctor review required',
                'consult_only' => false,
                'quiz_category' => 'Sexual health',
                'includes' => ['Private adaptive assessment', 'Licensed clinician review', 'Secure follow-up questions', 'Consultation or referral when indicated'],
                'flow' => ['Private intake', 'Safety screen', 'Clinician decides', 'Next step'],
            ],
            [
                'slug' => 'advanced-consult',
                'type' => 'service',
                'category' => 'Advanced wellness',
                'name' => 'Advanced Wellness Consultation',
                'short_description' => 'Consultation first for peptide, recovery and healthy-aging enquiries.',
                'long_description' => 'A clinician consultation for patients seeking to understand advanced wellness options. No therapy is promised, named or added to the cart; the clinician evaluates goals, evidence, risks and alternatives.',
                'price' => 350,
                'due_now' => 350,
                'price_note' => '45-minute consultation · no product included',
                'badge' => 'Consultation only',
                'consult_only' => true,
                'quiz_category' => 'Advanced wellness',
                'includes' => ['45-minute qualified clinician visit', 'Pre-visit medical history', 'Evidence and risk discussion', 'Documented advice and next steps'],
                'flow' => ['Book visit', 'Share history', 'Clinician consult', 'Advice or referral'],
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['slug' => $product['slug']], $product);
        }
    }
}
