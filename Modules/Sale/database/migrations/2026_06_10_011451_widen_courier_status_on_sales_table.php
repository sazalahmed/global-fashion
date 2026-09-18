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
        // courier_status holds the latest human-readable courier state. For
        // tracking_update / return_status webhooks this is the full tracking
        // message (e.g. "Consignment sent to KHULNA WAREHOUSE. Dispatch ID: …"),
        // which overflows the original varchar(40) and caused a 500. Widen it.
        Schema::table('sales', function (Blueprint $table) {
            $table->string('courier_status', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('courier_status', 40)->nullable()->change();
        });
    }
};
