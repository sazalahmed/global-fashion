<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-product overrides for the Size measurement chart.
        // The default chart lives on the Size variant attribute; this table
        // holds product-specific overrides for any (row, attribute value) pair.
        // FK names are spelled out — auto-generated ones blow MySQL's 64-char cap.
        Schema::create('product_size_chart_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_attribute_chart_row_id');
            $table->unsignedBigInteger('variant_attribute_value_id');
            $table->string('value', 64)->nullable();
            $table->timestamps();

            $table->foreign('variant_attribute_chart_row_id', 'pscv_row_fk')
                ->references('id')->on('variant_attribute_chart_rows')
                ->cascadeOnDelete();
            $table->foreign('variant_attribute_value_id', 'pscv_value_fk')
                ->references('id')->on('variant_attribute_values')
                ->cascadeOnDelete();

            $table->unique(
                ['product_id', 'variant_attribute_chart_row_id', 'variant_attribute_value_id'],
                'pscv_product_row_value_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_size_chart_values');
    }
};
