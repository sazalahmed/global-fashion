<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courier payouts are no longer typed in by hand — they are pulled from the
 * courier's settlement API. Storing the provider's own reference lets a sync
 * run repeatedly without duplicating a payout it already recorded.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('courier_withdrawals', function (Blueprint $table) {
            $table->string('settlement_id', 64)->nullable()->unique()->after('courier_provider');
        });
    }

    public function down(): void
    {
        Schema::table('courier_withdrawals', function (Blueprint $table) {
            $table->dropUnique(['settlement_id']);
            $table->dropColumn('settlement_id');
        });
    }
};
