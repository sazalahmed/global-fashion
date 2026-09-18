<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 50)->unique();
            $table->foreignId('expense_category_id')->constrained('expense_categories');
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('payment_account_id')->nullable()->constrained('accounts');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);
            $table->date('expense_date');
            $table->date('due_date')->nullable();
            $table->string('payment_method', 50);
            $table->string('reference', 100)->nullable();
            $table->text('description');
            $table->string('receipt_path', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('unpaid');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency', 20)->nullable();
            $table->date('next_recurring_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_category_id', 'expense_date']);
            $table->index('status');
            $table->index('payment_status');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
