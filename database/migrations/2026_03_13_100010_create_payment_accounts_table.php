<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Banks reference table
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Payment accounts (Cash, Mobile Banking, Bank, Card)
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('account_type', 30); // cash, mobile_banking, bank, card
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // Mobile Banking fields
            $table->string('mobile_bank_name', 50)->nullable(); // bKash, Nagad, Rocket, etc.
            $table->string('mobile_number', 20)->nullable();

            // Bank fields
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('bank_account_type', 30)->nullable(); // Savings, Current
            $table->string('bank_account_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_branch', 100)->nullable();

            // Card fields
            $table->string('card_type', 30)->nullable(); // Visa, MasterCard
            $table->string('card_holder_name', 100)->nullable();
            $table->string('card_number', 30)->nullable();

            // Common
            $table->decimal('service_charge', 5, 2)->default(0); // percentage
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_type');
            $table->index('is_active');
        });

        // Balance transfers between payment accounts
        Schema::create('balance_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transfers');
        Schema::dropIfExists('payment_accounts');
        Schema::dropIfExists('banks');
    }
};
