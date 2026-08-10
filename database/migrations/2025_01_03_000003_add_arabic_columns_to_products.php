<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable Arabic mirrors of the display columns, rather than a separate
     * translations table — this catalogue is small and fixed (7 seeded
     * products), so a join per request would add complexity without a real
     * benefit. ProductController falls back to the English column whenever
     * the Arabic one is null, so this is safe to add incrementally.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category_ar')->nullable()->after('category');
            $table->string('name_ar')->nullable()->after('name');
            $table->string('short_description_ar')->nullable()->after('short_description');
            $table->text('long_description_ar')->nullable()->after('long_description');
            $table->string('price_note_ar')->nullable()->after('price_note');
            $table->string('badge_ar')->nullable()->after('badge');
            $table->json('includes_ar')->nullable()->after('includes');
            $table->json('flow_ar')->nullable()->after('flow');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'category_ar', 'name_ar', 'short_description_ar', 'long_description_ar',
                'price_note_ar', 'badge_ar', 'includes_ar', 'flow_ar',
            ]);
        });
    }
};
