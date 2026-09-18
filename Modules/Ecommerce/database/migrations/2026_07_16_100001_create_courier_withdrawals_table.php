<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Courier withdrawals: COD money the courier (Steadfast) has collected is
     * paid out to the business periodically. Each payout is recorded here and
     * journaled DR Cash-Bank (net) + DR Courier Expense (fees) / CR Accounts
     * Receivable (gross), completing the COD money trail in the books.
     */
    public function up(): void
    {
        Schema::create('courier_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('withdrawal_number', 30)->unique();
            $table->string('courier_provider', 30)->default('steadfast');
            $table->decimal('amount', 15, 2);                       // net received in our account
            $table->decimal('courier_charge', 15, 2)->default(0);   // fees deducted by the courier
            $table->date('withdrawal_date');
            $table->foreignId('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->string('reference')->nullable();                // courier payment/settlement id
            $table->text('note')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['courier_provider', 'withdrawal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_withdrawals');
    }
};
