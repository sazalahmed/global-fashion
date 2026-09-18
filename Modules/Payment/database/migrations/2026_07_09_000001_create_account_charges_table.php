<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Standalone bank charges recorded against a payment account (maintenance
     * fees, SMS fees, cash-out charges, ...). Each posts a journal entry
     * DR Bank Charges & Fees / CR the account's cash/bank ledger.
     */
    public function up(): void
    {
        Schema::create('account_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_account_id')->constrained('payment_accounts')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('note', 500)->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_charges');
    }
};
