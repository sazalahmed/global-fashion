<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 30)->unique();
            $table->foreignId('factory_id')->constrained('factories')->restrictOnDelete();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->unsignedInteger('wasted_quantity')->default(0);
            $table->unsignedInteger('good_quantity')->default(0);
            $table->decimal('estimated_making_cost_per_unit', 15, 2)->default(0);
            $table->decimal('actual_making_cost_per_unit', 15, 2)->nullable();
            $table->decimal('estimated_total_cost', 15, 2)->default(0);
            $table->decimal('total_fabric_cost', 15, 2)->default(0);
            $table->decimal('total_making_cost', 15, 2)->default(0);
            $table->decimal('total_delivery_cost', 15, 2)->default(0);
            $table->decimal('total_other_cost', 15, 2)->default(0);
            $table->decimal('total_damage_cost', 15, 2)->default(0);
            $table->decimal('total_compensation', 15, 2)->default(0);
            $table->decimal('total_fabric_returned_cost', 15, 2)->default(0);
            $table->decimal('total_rm_waste_cost', 15, 2)->default(0);
            $table->decimal('total_rm_waste_abnormal_cost', 15, 2)->default(0);
            $table->decimal('total_product_waste_cost', 15, 2)->default(0);
            $table->decimal('total_product_waste_abnormal_cost', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);
            $table->decimal('advance_deducted', 15, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid');
            $table->decimal('running_weighted_avg_cost', 15, 2)->nullable();
            $table->decimal('final_cost_per_unit', 15, 2)->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['factory_id', 'status']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
