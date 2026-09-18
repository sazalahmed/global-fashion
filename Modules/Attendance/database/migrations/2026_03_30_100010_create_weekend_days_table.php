<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekend_days', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday..6=Saturday
            $table->boolean('is_weekend')->default(false);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();
            $table->unique(['day_of_week', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekend_days');
    }
};
