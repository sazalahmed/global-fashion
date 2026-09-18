<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds sales.delivered_at — the moment a sale first reached the
     * 'delivered' status. sale_date records when the sale was placed, so
     * courier deliveries that happen days later were invisible to any
     * "delivered on X" reporting; this column captures the actual delivery
     * time (stamped by the Sale model's saving hook).
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('courier_status_updated_at');
            $table->index('delivered_at');
        });

        // Backfill sales already delivered: the courier webhook's status
        // timestamp is the closest record of the real delivery moment,
        // falling back to the row's last update.
        DB::table('sales')
            ->where('status', 'delivered')
            ->whereNull('delivered_at')
            ->update(['delivered_at' => DB::raw('COALESCE(courier_status_updated_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['delivered_at']);
            $table->dropColumn('delivered_at');
        });
    }
};
