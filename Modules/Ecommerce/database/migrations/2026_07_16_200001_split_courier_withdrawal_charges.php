<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the single courier_charge into the two deductions couriers
     * actually make on a payout — per-parcel delivery charge and the COD
     * collection (recovery) fee — so the cashflow can show gross vs net.
     */
    public function up(): void
    {
        Schema::table('courier_withdrawals', function (Blueprint $table) {
            $table->decimal('delivery_charge', 15, 2)->default(0)->after('amount');
            $table->decimal('cod_charge', 15, 2)->default(0)->after('delivery_charge');
        });

        DB::table('courier_withdrawals')->update(['delivery_charge' => DB::raw('courier_charge')]);

        Schema::table('courier_withdrawals', function (Blueprint $table) {
            $table->dropColumn('courier_charge');
        });
    }

    public function down(): void
    {
        Schema::table('courier_withdrawals', function (Blueprint $table) {
            $table->decimal('courier_charge', 15, 2)->default(0)->after('amount');
        });

        DB::table('courier_withdrawals')->update(['courier_charge' => DB::raw('delivery_charge + cod_charge')]);

        Schema::table('courier_withdrawals', function (Blueprint $table) {
            $table->dropColumn(['delivery_charge', 'cod_charge']);
        });
    }
};
