<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained();
            $table->foreignId('supplier_id')->constrained();
            $table->string('return_number', 50)->unique();
            $table->date('return_date');
            $table->foreignId('return_type_id')->nullable()->constrained('purchase_return_types')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('reason')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_id');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('return_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
