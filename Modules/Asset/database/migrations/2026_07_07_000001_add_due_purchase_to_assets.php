<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds vendor + payment/due tracking to assets and a companion
     * asset_payments table so assets can be bought on credit and paid off over
     * time (mirrors the Expense module's paid/due pattern).
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('vendor_name')->nullable()->after('name');
            $table->string('vendor_invoice_no')->nullable()->after('vendor_name');
            $table->foreignId('payment_account_id')->nullable()->after('purchase_price');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('payment_account_id');
            $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            $table->string('payment_status', 20)->default('paid')->after('due_amount');
        });

        // Existing assets were recorded as fully paid.
        DB::table('assets')->update([
            'paid_amount'    => DB::raw('purchase_price'),
            'due_amount'     => 0,
            'payment_status' => 'paid',
        ]);

        Schema::create('asset_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->foreignId('payment_account_id')->nullable();
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_payments');

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_name', 'vendor_invoice_no', 'payment_account_id',
                'paid_amount', 'due_amount', 'payment_status',
            ]);
        });
    }
};
