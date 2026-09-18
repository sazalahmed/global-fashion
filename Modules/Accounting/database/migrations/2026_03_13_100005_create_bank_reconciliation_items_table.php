<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('journal_entry_line_id')->nullable()->constrained('journal_entry_lines')->nullOnDelete();
            $table->string('type', 30); // book_transaction, bank_statement
            $table->date('transaction_date');
            $table->string('description', 500);
            $table->string('reference', 100)->nullable();
            $table->decimal('amount', 15, 2);
            $table->boolean('is_reconciled')->default(false);
            $table->unsignedBigInteger('matched_item_id')->nullable();
            $table->timestamps();

            $table->index(['reconciliation_id', 'is_reconciled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
    }
};
