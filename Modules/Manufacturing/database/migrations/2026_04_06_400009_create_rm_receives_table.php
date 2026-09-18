<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rm_receives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('rm_purchase_orders')->cascadeOnDelete();
            $table->string('receive_number', 30)->unique();
            $table->date('receive_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_receives');
    }
};
