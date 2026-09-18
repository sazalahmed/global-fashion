<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20)->unique();
            $table->string('account_name', 255);
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('sub_type', 50);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('opening_balance_type', ['debit', 'credit'])->default('debit');
            $table->date('opening_balance_date')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_branch', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_type');
            $table->index('sub_type');
            $table->index('status');
            $table->index('is_bank_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
