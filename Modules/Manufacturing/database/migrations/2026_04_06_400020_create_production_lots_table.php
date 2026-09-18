<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->string('lot_number', 40)->unique();
            $table->date('delivery_date');
            $table->unsignedInteger('total_quantity')->default(0);
            $table->unsignedInteger('good_quantity')->default(0);
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->decimal('delivery_cost', 15, 2)->default(0);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->boolean('stock_added')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_lots');
    }
};
