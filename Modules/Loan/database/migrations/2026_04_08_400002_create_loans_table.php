<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number', 50)->unique();
            $table->foreignId('lender_id')->constrained('lenders');
            $table->decimal('principal_amount', 15, 2);
            $table->date('disbursement_date');
            $table->foreignId('disbursement_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->integer('total_installments');
            $table->decimal('installment_amount', 15, 2);
            $table->enum('frequency', ['weekly', 'bi_weekly', 'monthly'])->default('monthly');
            $table->date('start_date');
            $table->date('next_due_date')->nullable();
            $table->decimal('total_repaid', 15, 2)->default(0);
            $table->decimal('total_remaining', 15, 2);
            $table->enum('status', ['active', 'completed', 'overdue', 'cancelled'])->default('active');
            $table->string('reference', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('disbursement_date');
            $table->index('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
