<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_id')->constrained('production_damages')->cascadeOnDelete();
            $table->date('compensation_date');
            $table->decimal('amount', 15, 2);
            $table->string('method', 30);
            $table->unsignedBigInteger('payment_account_id')->nullable();
            $table->unsignedBigInteger('factory_payment_id')->nullable();
            $table->unsignedBigInteger('replacement_lot_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_compensations');
    }
};
