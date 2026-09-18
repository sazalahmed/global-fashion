<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('combo_id')->nullable()->after('variant_id');
            $table->string('combo_name')->nullable()->after('combo_id');
            $table->index('combo_id');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->dropIndex(['combo_id']);
            $table->dropColumn(['combo_id', 'combo_name']);
        });
    }
};
