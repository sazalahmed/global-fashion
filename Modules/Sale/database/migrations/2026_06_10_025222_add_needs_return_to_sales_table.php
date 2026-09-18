<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flags a sale whose courier reported a partial delivery: some items were
     * delivered and the rest sent back. The webhook carries no line items, so
     * the returned units can't be auto-restored — staff must file a SaleReturn.
     * This flag surfaces those sales so the return isn't missed.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('needs_return')->default(false)->after('courier_status_updated_at');
            $table->index('needs_return');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['needs_return']);
            $table->dropColumn('needs_return');
        });
    }
};
