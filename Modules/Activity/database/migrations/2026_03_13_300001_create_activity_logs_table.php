<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 50)->default('default');
            $table->text('description');
            $table->nullableMorphs('subject'); // The model being acted on
            $table->nullableMorphs('causer'); // The user performing the action
            $table->json('properties')->nullable(); // old/new values
            $table->string('event', 50)->nullable(); // created, updated, deleted
            $table->timestamps();
            $table->index('log_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
