<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable audit record: no updated_at (see ClinicalDecision::UPDATED_AT = null)
        // and no PATCH/DELETE route is ever registered against this table.
        Schema::create('clinical_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinician_id')->constrained('users')->cascadeOnDelete();
            $table->enum('outcome', [
                'approve_plan',
                'request_investigations',
                'request_more_info',
                'book_video_consult',
                'refer_in_person',
                'emergency_stop',
            ]);
            $table->text('rationale');
            $table->string('esigned_name');
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_decisions');
    }
};
