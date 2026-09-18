<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            // Meta campaign objective (e.g. OUTCOME_SALES, OUTCOME_ENGAGEMENT).
            // Displayed as a friendly "campaign type" label.
            $table->string('objective')->nullable()->after('campaign_id_external');
        });
    }

    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropColumn('objective');
        });
    }
};
