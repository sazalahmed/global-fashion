<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receive_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('quantity_received', 15, 4);
            $table->decimal('quantity_accepted', 15, 4);
            $table->decimal('quantity_rejected', 15, 4)->default(0);
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index('goods_receive_note_id');
            $table->index('purchase_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};
