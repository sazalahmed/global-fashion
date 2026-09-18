<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-editable description of the parcel contents, sent to the courier
     * (Steadfast `item_description`) alongside the buyer-facing note. Kept
     * separate from `notes` so staff can maintain both independently.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->text('item_description')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('item_description');
        });
    }
};
