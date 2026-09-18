<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            // Bangla name shown alongside the English name in the storefront
            // "Delivery Charge" dropdown, e.g. "Inside Dhaka (ঢাকার মধ্যে)".
            $table->string('bn_name', 150)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->dropColumn('bn_name');
        });
    }
};
