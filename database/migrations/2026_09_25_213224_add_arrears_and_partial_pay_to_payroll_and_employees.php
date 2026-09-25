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
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'due_salary')) {
                $table->decimal('due_salary', 15, 2)->default(0)->after('advance_balance');
            }
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_items', 'arrears_addition')) {
                $table->decimal('arrears_addition', 15, 2)->default(0)->after('advance_deduction');
            }
            if (!Schema::hasColumn('payroll_items', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->nullable()->after('net_salary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'due_salary')) {
                $table->dropColumn('due_salary');
            }
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_items', 'arrears_addition')) {
                $table->dropColumn('arrears_addition');
            }
            if (Schema::hasColumn('payroll_items', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
