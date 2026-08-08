<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->foreignId('intent_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->boolean('urgent_flag')->default(false);
            $table->string('urgent_reason')->nullable();
            $table->json('answers')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
