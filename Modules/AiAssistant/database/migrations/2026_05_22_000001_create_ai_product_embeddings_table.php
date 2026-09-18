<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_product_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('model', 60);
            $table->smallInteger('dimensions');
            $table->string('content_hash', 64);
            $table->json('embedding');
            $table->timestamps();

            $table->unique(['product_id', 'provider', 'model']);
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_product_embeddings');
    }
};
