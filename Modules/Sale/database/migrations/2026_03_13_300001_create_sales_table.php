<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->string('reference_number', 50)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name_snapshot', 150)->nullable();
            $table->string('customer_phone_snapshot', 30)->nullable();
            $table->text('customer_address')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('thana_id')->nullable()->constrained('thanas')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->enum('source', ['pos', 'store', 'ecommerce'])->default('store');
            $table->string('price_type', 20)->default('regular');
            $table->string('status', 30)->default('confirmed');
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_charge', 15, 2)->default(0);
            $table->string('courier_name', 50)->nullable();
            $table->string('courier_consignment_id', 100)->nullable();
            $table->string('courier_tracking_code', 60)->nullable();
            $table->string('courier_status', 40)->nullable();
            $table->string('courier_tracking_url', 500)->nullable();
            $table->timestamp('courier_status_updated_at')->nullable();
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);
            $table->decimal('courier_collected_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('staff_note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->string('redx_area_id', 50)->nullable();
            $table->string('redx_area_name', 100)->nullable();
            $table->string('pathao_city', 50)->nullable();
            $table->string('pathao_zone', 50)->nullable();
            $table->string('pathao_area_id', 50)->nullable();
            $table->decimal('pathao_weight', 6, 2)->nullable();

            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('sale_date');
            $table->index('source');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
