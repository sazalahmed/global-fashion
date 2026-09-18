<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('production_lots')->nullOnDelete();
            $table->foreignId('catalog_id')->constrained('catalogs')->restrictOnDelete();
            $table->foreignId('color_id')->constrained('mfg_colors')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('mfg_sizes')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity_wasted');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('waste_type', 30)->default('quality_rejection');
            $table->boolean('is_normal')->default(true);
            $table->decimal('waste_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('waste_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_wastes');
    }
};
