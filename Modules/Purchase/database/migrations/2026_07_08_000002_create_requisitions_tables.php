<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase requisitions: employees request products, admin reviews/approves,
     * then converts an approved requisition into a purchase order.
     */
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_number')->unique();
            $table->foreignId('requested_by')->nullable();
            $table->string('department')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->date('required_date')->nullable();
            $table->string('priority', 10)->default('normal'); // low|normal|high
            $table->string('status', 20)->default('pending');  // pending|approved|rejected|ordered|fulfilled|cancelled
            $table->text('note')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('purchase_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('requisition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
        Schema::dropIfExists('requisitions');
    }
};
