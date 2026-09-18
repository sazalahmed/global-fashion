<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('catalog_id')->constrained('catalogs')->restrictOnDelete();
            $table->foreignId('color_id')->constrained('mfg_colors')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('mfg_sizes')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->decimal('making_cost_per_unit', 15, 2)->default(0);
            $table->decimal('current_cost_per_unit', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_items');
    }
};
