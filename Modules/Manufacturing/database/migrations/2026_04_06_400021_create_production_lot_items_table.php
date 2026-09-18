<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_lot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('production_lots')->cascadeOnDelete();
            $table->foreignId('production_order_item_id')->constrained('production_order_items')->cascadeOnDelete();
            $table->foreignId('catalog_id')->constrained('catalogs')->restrictOnDelete();
            $table->foreignId('color_id')->constrained('mfg_colors')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('mfg_sizes')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('good_quantity')->default(0);
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_lot_items');
    }
};
