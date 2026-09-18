<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rm_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('avg_cost', 15, 2)->default(0);
            $table->decimal('last_cost', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['raw_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_stocks');
    }
};
