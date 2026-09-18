<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            // Stock direction this type applies: add to or subtract from stock.
            $table->enum('effect', ['addition', 'subtraction'])->default('addition');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('type')
                ->constrained('adjustment_types')->nullOnDelete();
        });

        // Base types matching the legacy enum, then backfill existing rows.
        $now = now();
        DB::table('adjustment_types')->insert([
            ['name' => 'Addition', 'effect' => 'addition', 'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Subtraction', 'effect' => 'subtraction', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $additionId = DB::table('adjustment_types')->where('name', 'Addition')->value('id');
        $subtractionId = DB::table('adjustment_types')->where('name', 'Subtraction')->value('id');
        DB::table('stock_adjustments')->where('type', 'addition')->update(['type_id' => $additionId]);
        DB::table('stock_adjustments')->where('type', 'subtraction')->update(['type_id' => $subtractionId]);
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('type_id');
        });
        Schema::dropIfExists('adjustment_types');
    }
};
