<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('district_name', 100);
            $table->string('bn_name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('district_name');
            $table->unique('district_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
