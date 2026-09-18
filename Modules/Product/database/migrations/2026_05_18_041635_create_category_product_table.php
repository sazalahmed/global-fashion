<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'category_id']);
            $table->index('category_id');
        });

        // Backfill: every existing product's singular category_id becomes its primary pivot row.
        $rows = DB::table('products')
            ->whereNotNull('category_id')
            ->select('id as product_id', 'category_id')
            ->get()
            ->map(fn ($r) => [
                'product_id'  => $r->product_id,
                'category_id' => $r->category_id,
                'is_primary'  => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ])
            ->all();

        if (!empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('category_product')->insertOrIgnore($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
