<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('expected_return_quantity', 15, 4)->default(0);
            $table->decimal('actual_issued_quantity', 15, 4)->default(0);
            $table->decimal('actual_returned_quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('estimated_consumption', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_materials');
    }
};
