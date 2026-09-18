<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('division', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('area')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('routing_number', 20)->nullable();
            $table->string('tin', 50)->nullable();
            $table->string('bin', 50)->nullable();
            $table->string('trade_license', 100)->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('payment_terms', 50)->default('Net 30');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('total_purchase', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('due_balance', 15, 2)->default(0);
            $table->decimal('advance_balance', 15, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->foreignId('supplier_group_id')->nullable()->constrained('supplier_groups')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('company_name');
            $table->index('phone');
            $table->index('supplier_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
