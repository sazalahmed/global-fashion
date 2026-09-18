<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Abandoned-checkout captures. One row per checkout attempt (keyed by the
     * page's one-time idempotency token, deduped by phone across attempts).
     * Each capture mirrors a Sale with status 'incompleted' so it surfaces in
     * the admin sales list's Incompleted tab; placing the order converts the
     * capture and removes the placeholder sale.
     */
    public function up(): void
    {
        Schema::create('incomplete_checkouts', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('customer_name', 255);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 255)->nullable();
            $table->string('address', 500)->nullable();
            $table->foreignId('district_id')->nullable();
            $table->foreignId('shipping_zone_id')->nullable();
            $table->json('cart')->nullable();
            $table->foreignId('converted_order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->timestamps();

            $table->index('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomplete_checkouts');
    }
};
