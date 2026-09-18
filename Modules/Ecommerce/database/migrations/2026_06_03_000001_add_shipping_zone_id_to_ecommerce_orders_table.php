<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            // Remember which shipping zone the order shipped under so the
            // confirmation page can show that zone's estimated delivery days.
            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->after('shipping_charge')
                ->constrained('shipping_zones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_zone_id');
        });
    }
};
