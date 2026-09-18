<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Merge the separate "Loans Taken" section back into Personal Loans as a
     * two-way ledger: one record per person supporting give/receive AND
     * take/pay-back. outstanding_balance becomes signed — positive means the
     * person owes the business, negative means the business owes the person.
     * opening_balance is likewise signed (negative = we owed them at setup).
     */
    public function up(): void
    {
        // Old taken-direction records stored opening_balance on the payable
        // side; in the signed model that is a negative receivable.
        DB::table('borrowers')
            ->where('direction', 'taken')
            ->update(['opening_balance' => DB::raw('-opening_balance')]);

        // Recompute every balance with the unified signed formula. Also
        // self-heals any stale stored balances.
        DB::table('borrowers')->update([
            'outstanding_balance' => DB::raw(
                'ROUND(opening_balance + total_lent - total_recovered - total_taken + total_returned, 2)'
            ),
        ]);

        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropIndex(['direction']);
            $table->dropColumn('direction');
        });
    }

    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $table->string('direction', 10)->default('given')->after('address')->index();
        });

        // Best-effort split: records whose activity is only on the taken side
        // go back to the taken section with a positive payable balance.
        DB::table('borrowers')
            ->where('total_taken', '>', 0)
            ->where('total_lent', 0)
            ->update(['direction' => 'taken', 'opening_balance' => DB::raw('-opening_balance')]);

        DB::table('borrowers')->where('direction', 'taken')->update([
            'outstanding_balance' => DB::raw('ROUND(GREATEST(opening_balance + total_taken - total_returned, 0), 2)'),
        ]);
        DB::table('borrowers')->where('direction', 'given')->update([
            'outstanding_balance' => DB::raw('ROUND(GREATEST(opening_balance + total_lent - total_recovered, 0), 2)'),
        ]);
    }
};
