<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('pharmacist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('physician_verified')->default(false);
            $table->boolean('identity_match')->default(false);
            $table->boolean('duplicate_fill_check')->default(false);
            $table->boolean('dur_review')->default(false);
            $table->boolean('counselling_required')->default(false);
            $table->enum('status', ['awaiting_decision', 'ready_for_review', 'accepted', 'dispensed'])->default('awaiting_decision');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
