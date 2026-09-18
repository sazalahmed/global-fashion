<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Delivery Challan and Installment (Kisti) features were removed —
 * orders now ship through the Sale + Sales-Return flow and instalment
 * sales are handled via partial payments against a Sale. Drop the
 * tables they owned, plus the FK column they left dangling on
 * ecommerce_orders.
 */
return new class extends Migration {
    public function up(): void
    {
        // FK first so the parent table can be dropped without a constraint
        // violation.
        if (Schema::hasTable('ecommerce_orders') && Schema::hasColumn('ecommerce_orders', 'delivery_challan_id')) {
            Schema::table('ecommerce_orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('delivery_challan_id');
            });
        }

        // Children before parents (installment_schedules FK → installment_plans;
        // delivery_challan_items FK → delivery_challans).
        Schema::dropIfExists('installment_schedules');
        Schema::dropIfExists('installment_plans');
        Schema::dropIfExists('delivery_challan_items');
        Schema::dropIfExists('delivery_challans');
    }

    public function down(): void
    {
        // Intentional no-op. These features are gone for good — recreating
        // the empty tables would only give the misleading impression that
        // the modules are coming back.
    }
};
