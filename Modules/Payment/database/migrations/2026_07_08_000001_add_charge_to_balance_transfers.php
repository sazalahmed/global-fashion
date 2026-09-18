<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank charge on balance transfers — the fee a bank/mobile provider deducts
     * when moving money between accounts. Booked as a Bank Charges expense.
     */
    public function up(): void
    {
        Schema::table('balance_transfers', function (Blueprint $table) {
            $table->decimal('charge', 15, 2)->default(0)->after('amount');
            $table->foreignId('journal_entry_id')->nullable()->after('charge');
        });

        // The chart of accounts already ships 5170 "Bank Charges & Fees" —
        // the charge journal entries post there (see PaymentAccountController).
    }

    public function down(): void
    {
        Schema::table('balance_transfers', function (Blueprint $table) {
            $table->dropColumn(['charge', 'journal_entry_id']);
        });
    }
};
