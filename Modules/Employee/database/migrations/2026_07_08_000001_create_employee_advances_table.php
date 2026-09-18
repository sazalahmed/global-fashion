<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Individual employee advance transactions (advances given + recoveries) so
     * each movement is auditable and a per-employee ledger can be built. The
     * running total is mirrored on employees.advance_balance.
     */
    public function up(): void
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('advance_number')->unique();
            $table->enum('type', ['advance', 'recovery']);
            $table->decimal('amount', 15, 2);
            $table->date('advance_date');
            $table->foreignId('payment_account_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_advances');
    }
};
