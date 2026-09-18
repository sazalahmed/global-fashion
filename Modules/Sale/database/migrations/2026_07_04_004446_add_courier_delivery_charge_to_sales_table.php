<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds sales.courier_delivery_charge — the fee the courier charges us for
     * a consignment, reported by the courier webhook (Steadfast sends
     * delivery_charge on every delivery_status event). Stored only; nothing
     * calculates from it yet (planned for cashflow). Not to be confused with
     * shipping_charge, which is what the customer is billed.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('courier_delivery_charge', 15, 2)->nullable()->after('courier_collected_amount');
        });

        // Backfill from webhook payloads already recorded as tracking events —
        // take each sale's most recent delivery_status payload carrying a charge.
        $rows = DB::table('courier_tracking_events')
            ->whereNotNull(DB::raw("JSON_EXTRACT(payload, '$.delivery_charge')"))
            ->orderBy('id')
            ->get(['sale_id', 'payload']);

        foreach ($rows as $row) {
            $charge = json_decode($row->payload, true)['delivery_charge'] ?? null;
            if (is_numeric($charge)) {
                DB::table('sales')->where('id', $row->sale_id)
                    ->update(['courier_delivery_charge' => (float) $charge]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('courier_delivery_charge');
        });
    }
};
