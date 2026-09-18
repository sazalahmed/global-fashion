<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id', 36)->nullable()->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('query', 500);
            $table->unsignedBigInteger('vector_top_id')->nullable();
            $table->unsignedBigInteger('keyword_top_id')->nullable();
            $table->unsignedBigInteger('fused_top_id')->nullable();
            $table->unsignedBigInteger('clicked_id')->nullable();
            $table->boolean('converted')->default(false);
            $table->json('result_ids')->nullable();
            $table->integer('total_results')->default(0);
            $table->integer('latency_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_search_logs');
    }
};
