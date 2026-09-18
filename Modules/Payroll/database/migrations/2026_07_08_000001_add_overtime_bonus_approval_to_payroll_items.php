<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payroll overhaul: explicit editable earning inputs (overtime/bonus/
     * commission), an editable absent deduction, and per-employee approval.
     */
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('overtime', 15, 2)->default(0)->after('basic_salary');
            $table->decimal('overtime_hours', 6, 2)->default(0)->after('overtime');
            $table->decimal('bonus', 15, 2)->default(0)->after('overtime_hours');
            $table->decimal('commission', 15, 2)->default(0)->after('bonus');
            $table->decimal('absent_deduction', 15, 2)->default(0)->after('advance_deduction');
            $table->string('status', 20)->default('pending')->after('net_salary'); // pending|approved
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn([
                'overtime', 'overtime_hours', 'bonus', 'commission',
                'absent_deduction', 'status', 'approved_at', 'approved_by',
            ]);
        });
    }
};
