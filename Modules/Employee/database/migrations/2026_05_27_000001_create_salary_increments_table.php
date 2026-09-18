<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_increments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('previous_salary', 15, 2);
            $table->decimal('new_salary', 15, 2);
            $table->enum('increment_type', ['amount', 'percentage']);
            $table->decimal('increment_value', 15, 3);
            $table->text('note')->nullable();
            $table->foreignId('incremented_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('applied_at');
            $table->timestamps();

            $table->index(['employee_id', 'applied_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_increments');
    }
};
