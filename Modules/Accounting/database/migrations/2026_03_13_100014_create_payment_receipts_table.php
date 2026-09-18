<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('payment_id')->constrained('payments');
            $table->string('receipt_type', 20); // payment_received, payment_made
            $table->string('party_name', 255);
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50);
            $table->date('receipt_date');
            $table->text('description')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
