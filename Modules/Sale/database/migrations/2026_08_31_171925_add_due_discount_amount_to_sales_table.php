<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Running total of due discount ever written off against this sale via
     * the customer Due Receive flow. Persisted (not derived on the fly) so
     * every place that recomputes due_amount — SaleService::updatePaymentStatus(),
     * PaymentService's allocation loop, CustomerService::applyAdvanceToDues() —
     * subtracts the exact same number and never "un-applies" a discount.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('due_discount_amount', 15, 2)->default(0)->after('due_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('due_discount_amount');
        });
    }
};
