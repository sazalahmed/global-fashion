<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_check_reports', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('risk_level', 20)->index();
            $table->json('aggregate');
            $table->json('courier_data')->nullable();
            $table->json('reports')->nullable();
            $table->string('source', 20)->default('bdcourier');
            $table->boolean('not_found')->default(false);
            $table->timestamp('last_checked_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_check_reports');
    }
};
