<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personal loans = money the business lends out to a borrower (a running
     * receivable account with flexible disbursements + repayments). Mirrors the
     * Lender pattern on the asset side.
     */
    public function up(): void
    {
        Schema::create('borrowers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('total_lent', 15, 2)->default(0);
            $table->decimal('total_recovered', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('personal_loan_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')->constrained()->cascadeOnDelete();
            $table->string('txn_number')->unique();
            $table->enum('type', ['disbursement', 'repayment']);
            $table->decimal('amount', 15, 2);
            $table->date('txn_date');
            $table->foreignId('payment_account_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('borrower_id');
        });

        // Seed the receivable account personal-loan journal entries post to.
        $exists = DB::table('accounts')->where('account_code', '1015')->exists();
        if (! $exists) {
            DB::table('accounts')->insert([
                'account_code' => '1015',
                'account_name' => 'Loans & Advances (Receivable)',
                'account_type' => 'asset',
                'sub_type'     => 'current_asset',
                'description'  => 'Money lent out to borrowers (personal loans given by the business).',
                'is_system'    => 1,
                'status'       => 'active',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_loan_transactions');
        Schema::dropIfExists('borrowers');
        DB::table('accounts')->where('account_code', '1015')->where('is_system', 1)->delete();
    }
};
