<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_positions', function (Blueprint $table) {
            $table->id();
            // NULL category_id = the global (All Categories / main shop) bucket.
            $table->foreignId('category_id')->nullable()
                ->constrained('categories')->cascadeOnDelete();
            $table->string('positionable_type', 32); // 'product' | 'combo'
            $table->unsignedBigInteger('positionable_id');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // Protects non-null-category rows. Global (NULL) uniqueness is
            // enforced in app code via updateOrCreate (MySQL treats NULLs as distinct).
            $table->unique(['category_id', 'positionable_type', 'positionable_id'], 'catalog_positions_unique');
            $table->index(['category_id', 'position']);
            $table->index(['positionable_type', 'positionable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_positions');
    }
};
