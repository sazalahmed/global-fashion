<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Overtime tracking: whole-day overtime for work on a weekend/holiday, and
     * extra hours worked after the shift end on a normal day. Consumed by payroll.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_overtime')->default(false)->after('status');
            $table->decimal('overtime_hours', 6, 2)->default(0)->after('is_overtime');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['is_overtime', 'overtime_hours']);
        });
    }
};
