<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Curated products attached to a homepage section, with an optional
     * per-product thumbnail override (falls back to the product thumbnail
     * when null) and an explicit display order.
     */
    public function up(): void
    {
        Schema::create('homepage_section_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('thumbnail', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['homepage_section_id', 'product_id']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_section_products');
    }
};
