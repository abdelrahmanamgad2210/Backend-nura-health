<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_case_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->enum('severity', ['red', 'amber', 'green']);
            $table->string('reference');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_signals');
    }
};
