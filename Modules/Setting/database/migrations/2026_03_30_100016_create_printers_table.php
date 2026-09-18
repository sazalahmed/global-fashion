<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('ip_address', 45);
            $table->unsignedSmallInteger('port')->default(9100);
            $table->string('printer_type', 30)->default('thermal'); // thermal, thermal_58, a4, label
            $table->string('connection_type', 20)->default('network'); // network, usb, bluetooth
            $table->unsignedTinyInteger('paper_width')->default(80); // mm: 58, 80, 210
            $table->string('purpose', 30)->default('receipt'); // receipt, invoice, barcode, kitchen, report
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index('branch_id');
            $table->index('is_active');
            $table->unique(['ip_address', 'port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};
