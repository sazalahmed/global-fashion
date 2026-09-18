<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Leave types are now managed records (leave_types table), so the leaves
     * column must accept any code — widen the fixed enum to a string.
     */
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('leave_type', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->enum('leave_type', ['casual', 'sick', 'annual', 'maternity', 'paternity', 'unpaid'])->change();
        });
    }
};
