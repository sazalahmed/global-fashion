<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Pivot — each zone covers many districts; each district can only
        // belong to one zone (unique on district_id keeps checkout lookup
        // deterministic, no need for "first match wins" logic).
        Schema::create('shipping_zone_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('district_id');
            $table->index('shipping_zone_id');
        });

        // The original migration stored district IDs in a JSON `divisions`
        // column. We've moved to the pivot above; drop the dead column.
        if (Schema::hasColumn('shipping_zones', 'divisions')) {
            Schema::table('shipping_zones', function (Blueprint $table) {
                $table->dropColumn('divisions');
            });
        }
    }

    public function down(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->json('divisions')->nullable()->after('name');
        });
        Schema::dropIfExists('shipping_zone_districts');
    }
};
