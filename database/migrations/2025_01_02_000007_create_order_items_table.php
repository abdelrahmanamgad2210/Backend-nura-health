<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Nullable + snapshot columns below: a later product edit or delete must
            // never change what an already-placed order shows.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_slug');
            $table->string('product_name');
            $table->enum('product_type', ['direct', 'clinical', 'service']);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('due_now_amount', 8, 2);
            $table->foreignId('clinical_case_id')->nullable()->constrained('clinical_cases')->nullOnDelete();
            $table->enum('fulfilment_status', [
                'pending',
                'blocked',
                'approved_awaiting_acceptance',
                'accepted_by_patient',
                'dispensing',
                'fulfilled',
                'cancelled',
            ])->default('pending');
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
