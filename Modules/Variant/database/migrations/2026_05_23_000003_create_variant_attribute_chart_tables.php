<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Measurement rows (Chest, Waist, …) attached to a variant attribute.
        // One chart per attribute — typically the Size attribute.
        Schema::create('variant_attribute_chart_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_attribute_id')
                ->constrained('variant_attributes')
                ->cascadeOnDelete();
            $table->string('label', 64);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['variant_attribute_id', 'sort_order'], 'vacr_attr_sort_idx');
        });

        // Cells: row × attribute value = stored measurement string.
        // FK names spelled out — auto-generated ones exceed MySQL's 64-char limit.
        Schema::create('variant_attribute_chart_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variant_attribute_chart_row_id');
            $table->unsignedBigInteger('variant_attribute_value_id');
            $table->string('value', 64)->nullable();
            $table->timestamps();

            $table->foreign('variant_attribute_chart_row_id', 'vacv_row_fk')
                ->references('id')->on('variant_attribute_chart_rows')
                ->cascadeOnDelete();
            $table->foreign('variant_attribute_value_id', 'vacv_value_fk')
                ->references('id')->on('variant_attribute_values')
                ->cascadeOnDelete();

            $table->unique(
                ['variant_attribute_chart_row_id', 'variant_attribute_value_id'],
                'vacv_row_value_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_chart_values');
        Schema::dropIfExists('variant_attribute_chart_rows');
    }
};
