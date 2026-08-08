<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('category');
            $table->enum('risk_flag', ['red', 'amber', 'green'])->default('green');
            $table->text('ai_draft_summary')->nullable();
            $table->enum('status', ['new', 'in_review', 'decided'])->default('new');
            $table->foreignId('assigned_clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_cases');
    }
};
