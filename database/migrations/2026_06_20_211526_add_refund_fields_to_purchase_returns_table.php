<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            // How the return is settled: credit_note (reduces payable), replacement, or refund (cash back)
            $table->string('resolution', 20)->default('credit_note')->after('reason');
            // Cash refunded by the supplier (only when resolution = refund)
            $table->decimal('refunded_amount', 15, 2)->default(0)->after('total');
            $table->foreignId('refund_account_id')->nullable()->after('refunded_amount')
                ->constrained('payment_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refund_account_id');
            $table->dropColumn(['resolution', 'refunded_amount']);
        });
    }
};
