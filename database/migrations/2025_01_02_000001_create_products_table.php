<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->enum('type', ['direct', 'clinical', 'service']);
            $table->string('category');
            $table->string('name');
            $table->string('short_description');
            $table->text('long_description');
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('due_now', 8, 2);
            $table->string('price_note')->nullable();
            $table->string('badge')->nullable();
            $table->boolean('consult_only')->default(false);
            $table->string('quiz_category')->nullable();
            $table->json('includes');
            $table->json('flow');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
