<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('category', 30)->default('fabric'); // fabric, thread, button, zipper, other
            $table->string('unit', 20)->default('yard'); // yard, meter, kg, piece, roll
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('last_purchase_price', 15, 2)->default(0);
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
