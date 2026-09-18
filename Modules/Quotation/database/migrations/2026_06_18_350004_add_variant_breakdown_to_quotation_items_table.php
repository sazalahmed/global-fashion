<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-variant breakdown for combined variable-product lines, e.g.
     * [{"name":"XS / Red","qty":12,"price":1700}, ...]. Lets the edit/show
     * views re-render the full chip breakdown (name + qty + price) instead of
     * only the variant names kept in custom_note.
     */
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->json('variant_breakdown')->nullable()->after('custom_note');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('variant_breakdown');
        });
    }
};
