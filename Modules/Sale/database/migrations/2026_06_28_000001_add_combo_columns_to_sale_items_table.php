<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tag sale lines that came from a combo so the combo can be grouped, edited
     * as a unit, and reported on:
     *   - combo_id     → the source combo (nullOnDelete; the line survives)
     *   - combo_group  → groups the component rows of ONE combo instance in a
     *                    sale (independent of combo_id, so it survives deletion
     *                    and distinguishes the same combo added twice)
     *   - combo_name   → snapshot label, survives rename/delete
     *   - combo_price  → the combo's effective price (per single combo) at order
     *                    time; the component line subtotals are allocated to sum
     *                    to this × combo qty
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('combo_id')->nullable()->after('product_id')
                ->constrained('combos')->nullOnDelete();
            $table->uuid('combo_group')->nullable()->after('combo_id')->index();
            $table->string('combo_name')->nullable()->after('combo_group');
            $table->decimal('combo_price', 15, 2)->nullable()->after('combo_name');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('combo_id');
            $table->dropColumn(['combo_group', 'combo_name', 'combo_price']);
        });
    }
};
