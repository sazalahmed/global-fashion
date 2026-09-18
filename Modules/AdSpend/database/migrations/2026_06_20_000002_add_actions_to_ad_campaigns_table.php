<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            // Raw Meta insights "actions" breakdown (action_type => value), so we
            // can surface the objective-specific result (purchases / messages /
            // engagements) without a column per metric.
            $table->json('actions')->nullable()->after('conversions');
        });
    }

    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropColumn('actions');
        });
    }
};
