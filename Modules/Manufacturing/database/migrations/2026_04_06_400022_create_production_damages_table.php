<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->nullable()->constrained('production_lots')->nullOnDelete();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('catalog_id')->constrained('catalogs')->restrictOnDelete();
            $table->foreignId('color_id')->constrained('mfg_colors')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('mfg_sizes')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('estimated_cost_per_unit', 15, 2)->default(0);
            $table->decimal('total_damage_cost', 15, 2)->default(0);
            $table->string('damage_type', 30)->default('factory_fault');
            $table->string('responsibility', 20)->default('factory');
            $table->decimal('compensation_amount', 15, 2)->default(0);
            $table->string('compensation_status', 20)->default('pending');
            $table->decimal('compensation_received', 15, 2)->default(0);
            $table->string('compensation_method', 30)->nullable();
            $table->date('damage_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['production_order_id', 'compensation_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_damages');
    }
};
