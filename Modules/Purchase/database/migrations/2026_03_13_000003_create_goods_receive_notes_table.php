<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receive_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained();
            $table->string('grn_number', 50)->unique();
            $table->date('received_date');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->constrained('users');
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_id');
            $table->index('received_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receive_notes');
    }
};
