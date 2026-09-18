<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoice-level (whole-order) discount and VAT/Tax, stored separately from
     * the per-line amounts that already sum into discount_amount / tax_amount.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('order_discount', 15, 2)->default(0)->after('discount_amount');
            $table->string('order_tax_mode', 10)->default('percent')->after('order_discount'); // percent|flat
            $table->decimal('order_tax_rate', 6, 2)->default(0)->after('order_tax_mode');
            $table->decimal('order_tax_amount', 15, 2)->default(0)->after('order_tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['order_discount', 'order_tax_mode', 'order_tax_rate', 'order_tax_amount']);
        });
    }
};
