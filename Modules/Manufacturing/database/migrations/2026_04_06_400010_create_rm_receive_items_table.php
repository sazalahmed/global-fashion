<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rm_receive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receive_id')->constrained('rm_receives')->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained('rm_purchase_items')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('quantity_received', 15, 4);
            $table->decimal('quantity_damaged', 15, 4)->default(0);
            $table->text('damage_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_receive_items');
    }
};
