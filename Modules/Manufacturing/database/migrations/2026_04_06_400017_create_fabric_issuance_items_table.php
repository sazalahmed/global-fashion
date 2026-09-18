<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabric_issuance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuance_id')->constrained('fabric_issuances')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->foreignId('bom_item_id')->nullable()->constrained('production_order_materials')->nullOnDelete();
            $table->decimal('quantity_issued', 15, 4);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabric_issuance_items');
    }
};
