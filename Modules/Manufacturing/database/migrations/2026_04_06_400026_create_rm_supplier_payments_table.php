<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rm_supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('raw_material_suppliers')->restrictOnDelete();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30);
            $table->unsignedBigInteger('payment_account_id')->nullable();
            $table->string('payment_type', 30)->default('against_po');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_supplier_payments');
    }
};
