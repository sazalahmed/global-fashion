<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 30)->unique();
            $table->string('name');
            $table->foreignId('asset_category_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->boolean('is_depreciable')->default(true);
            $table->enum('depreciation_method', ['straight_line', 'declining_balance'])->default('straight_line');
            $table->integer('useful_life_years')->default(5);
            $table->date('last_depreciation_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->enum('status', ['active', 'disposed', 'under_maintenance', 'written_off'])->default('active');
            $table->text('warranty_info')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
