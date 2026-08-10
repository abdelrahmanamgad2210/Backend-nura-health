<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The intake assessment is answerable without an account — identity
     * verification is its own in-quiz step, not a login gate. Only checkout
     * still requires authentication. Guest assessment rows are identified by
     * a long-lived `guest_token` cookie instead of `user_id`, same pattern as
     * cart_items (see 2025_01_03_000001_make_cart_items_guest_friendly).
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_token')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('guest_token');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
