<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adjustment "types" only ever held Addition and Subtraction — the direction,
 * which stock_adjustments.type already stores as an enum and which every stock
 * calculation reads. Managing that binary through a CRUD earned nothing.
 *
 * The list that actually needed managing was the reason, hardcoded in the
 * adjustment form's Blade template. This turns it into a real table and retires
 * the types CRUD along with its redundant type_id column.
 *
 * Reasons stay independent of direction: the user picks the direction and the
 * reason separately, exactly as the form already worked.
 */
return new class extends Migration
{
    /** The reasons the form offered as hardcoded <option> values. */
    private const SEED = ['Damage', 'Expired', 'Lost', 'Correction', 'Opening Stock', 'Other'];

    public function up(): void
    {
        Schema::create('adjustment_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('adjustment_reasons')->insert(
            collect(self::SEED)->map(fn ($name, $i) => [
                'name' => $name, 'is_active' => true, 'sort_order' => $i,
                'created_at' => $now, 'updated_at' => $now,
            ])->all()
        );

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('reason_id')->nullable()->after('type')
                ->constrained('adjustment_reasons')->nullOnDelete();
        });

        // Carry the free-text reason across. Existing values are the old option
        // keys ('damage', 'opening_stock', 'other'), so match on a normalised
        // form of the name rather than the label.
        foreach (DB::table('adjustment_reasons')->get(['id', 'name']) as $reason) {
            DB::table('stock_adjustments')
                ->whereNull('reason_id')
                ->whereRaw('LOWER(REPLACE(reason, " ", "_")) = ?', [Str::snake(strtolower($reason->name))])
                ->update(['reason_id' => $reason->id]);
        }

        // Anything unmatched (a hand-typed reason) is kept verbatim in `reason`
        // and pointed at Other, so no adjustment loses its explanation.
        $other = DB::table('adjustment_reasons')->where('name', 'Other')->value('id');
        DB::table('stock_adjustments')->whereNull('reason_id')->update(['reason_id' => $other]);

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn('type_id');
        });

        Schema::dropIfExists('adjustment_types');
    }

    public function down(): void
    {
        Schema::create('adjustment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->enum('effect', ['addition', 'subtraction'])->default('addition');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('adjustment_types')->insert([
            ['name' => 'Addition', 'effect' => 'addition', 'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Subtraction', 'effect' => 'subtraction', 'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('type')
                ->constrained('adjustment_types')->nullOnDelete();
        });

        // Rebuild type_id from the direction still stored on each row.
        foreach (DB::table('adjustment_types')->get(['id', 'effect']) as $type) {
            DB::table('stock_adjustments')->where('type', $type->effect)->update(['type_id' => $type->id]);
        }

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['reason_id']);
            $table->dropColumn('reason_id');
        });

        Schema::dropIfExists('adjustment_reasons');
    }
};
