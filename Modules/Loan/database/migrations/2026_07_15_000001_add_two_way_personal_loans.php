<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personal loans gain a mirrored "Loans Taken" section: besides lending to
     * a person (direction=given, receivable), the business can take a loan
     * FROM a person and pay it back (direction=taken, payable). Each party
     * record belongs to one direction and keeps its own one-way ledger; money
     * taken posts to a dedicated liability account.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE personal_loan_transactions MODIFY type ENUM('disbursement','repayment','loan_taken','loan_return') NOT NULL");

        Schema::table('borrowers', function (Blueprint $table) {
            $table->string('direction', 10)->default('given')->after('address')->index();
            $table->decimal('total_taken', 15, 2)->default(0)->after('total_recovered');
            $table->decimal('total_returned', 15, 2)->default(0)->after('total_taken');
        });

        // Seed the liability account loan-taken journal entries post to.
        $exists = DB::table('accounts')->where('account_code', '2025')->exists();
        if (! $exists) {
            DB::table('accounts')->insert([
                'account_code' => '2025',
                'account_name' => 'Personal Loans (Payable)',
                'account_type' => 'liability',
                'sub_type'     => 'current_liability',
                'description'  => 'Money the business has borrowed from individuals (personal loans taken).',
                'is_system'    => 1,
                'status'       => 'active',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropColumn(['direction', 'total_taken', 'total_returned']);
        });

        // Only safe while no rows use the new types; delete them first.
        DB::table('personal_loan_transactions')->whereIn('type', ['loan_taken', 'loan_return'])->delete();
        DB::statement("ALTER TABLE personal_loan_transactions MODIFY type ENUM('disbursement','repayment') NOT NULL");

        DB::table('accounts')->where('account_code', '2025')->where('is_system', 1)->delete();
    }
};
