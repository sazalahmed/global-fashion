<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->enum('direction', ['receive', 'pay']);
            $table->string('party_type', 30)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('payment_type', 30);
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50);
            $table->foreignId('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->date('payment_date');
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['direction', 'party_type', 'party_id']);
            $table->index('payment_date');
            $table->index('payment_method');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
