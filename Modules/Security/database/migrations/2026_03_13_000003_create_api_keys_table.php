<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('key_hash');
            $table->string('key_prefix', 40);
            $table->enum('environment', ['live', 'test'])->default('live');
            $table->timestamp('expires_at')->nullable();
            $table->integer('rate_limit')->default(60);
            $table->json('permissions')->nullable();
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
