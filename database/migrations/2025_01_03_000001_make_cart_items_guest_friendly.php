<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products and the cart are public (no account needed to browse or add
     * items). Only checkout — which creates a real Order tied to a user —
     * still requires authentication. Guest cart rows are identified by a
     * long-lived `guest_token` cookie instead of `user_id`.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_token')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('guest_token');
            $table->foreignId('user_id')->nullable(false)->change();
            $table->unique(['user_id', 'product_id']);
        });
    }
};
