<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->json('tracking_data')->nullable()->after('fraud_score');
            $table->timestamp('purchase_reported_at')->nullable()->after('tracking_data');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn(['tracking_data', 'purchase_reported_at']);
        });
    }
};
