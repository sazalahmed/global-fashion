<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make the expenses.description column nullable — a description is now
     * optional when recording an expense.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE expenses MODIFY description TEXT NULL');
    }

    public function down(): void
    {
        // Backfill any nulls so the NOT NULL constraint can be restored.
        DB::statement("UPDATE expenses SET description = '' WHERE description IS NULL");
        DB::statement('ALTER TABLE expenses MODIFY description TEXT NOT NULL');
    }
};
