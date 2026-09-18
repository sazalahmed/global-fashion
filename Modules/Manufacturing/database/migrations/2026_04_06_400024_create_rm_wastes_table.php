<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rm_wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('production_lots')->nullOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('quantity_wasted', 15, 4);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->string('waste_type', 30)->default('cutting');
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
        Schema::dropIfExists('rm_wastes');
    }
};
