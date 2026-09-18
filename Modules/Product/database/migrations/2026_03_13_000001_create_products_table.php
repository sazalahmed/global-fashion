<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('model', 100)->nullable();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('purchase_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('sale_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('product_type', 20)->default('simple');

            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->decimal('sell_price', 15, 2)->default(0.00);
            $table->decimal('wholesale_price', 15, 2)->nullable();
            $table->decimal('vat_rate', 5, 2)->default(15.00);
            $table->string('vat_inclusive', 10)->default('yes');
            $table->string('discount_type', 20)->default('none');
            $table->decimal('discount_value', 15, 2)->nullable();

            $table->text('description')->nullable();
            $table->longText('long_description')->nullable();
            $table->string('warranty', 255)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('country_of_origin', 100)->nullable();

            $table->unsignedInteger('min_stock_alert')->default(10);
            $table->unsignedInteger('max_stock_level')->nullable();
            $table->string('valuation_method', 20)->default('fifo');

            $table->string('status', 20)->default('active');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('show_in_pos')->default(true);
            $table->boolean('track_stock')->default(true);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('ecom_sync')->default(false);
            $table->boolean('ecom_visible')->default(false);

            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('thumbnail', 500)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('brand_id');
            $table->index('supplier_id');
            $table->index('unit_id');
            $table->index('product_type');
            $table->index('status');
            $table->index('position');
            $table->index('model');
            $table->index('show_in_pos');
            $table->fullText(['name', 'description']);
            $table->index('created_by');
        });

        // AI search fulltext index (separate from the basic name+description fulltext above)
        DB::statement('ALTER TABLE products ADD FULLTEXT products_ai_fulltext_idx (name, sku, description, long_description)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
