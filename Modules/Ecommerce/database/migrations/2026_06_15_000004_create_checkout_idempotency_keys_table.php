<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time tokens that make storefront order placement idempotent. Each
     * rendered checkout form carries a unique token; the first POST claims it
     * (unique constraint), so a duplicate submit — double-click, raced POST,
     * or client retry — cannot create a second order.
     */
    public function up(): void
    {
        Schema::create('checkout_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_idempotency_keys');
    }
};
