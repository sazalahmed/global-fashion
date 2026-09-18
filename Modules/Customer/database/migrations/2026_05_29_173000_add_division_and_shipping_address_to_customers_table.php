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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'division')) {
                $table->string('division', 50)->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('customers', 'shipping_address')) {
                $table->string('shipping_address', 500)->nullable()->after('address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'division')) {
                $table->dropColumn('division');
            }
            if (Schema::hasColumn('customers', 'shipping_address')) {
                $table->dropColumn('shipping_address');
            }
        });
    }
};
