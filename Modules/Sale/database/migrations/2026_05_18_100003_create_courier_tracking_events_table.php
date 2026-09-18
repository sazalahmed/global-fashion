<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('courier_provider', 40)->index();
            $table->string('event_type', 40)->index();
            $table->string('status', 60)->nullable();
            $table->text('message')->nullable();
            $table->string('consignment_id', 100)->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tracking_events');
    }
};
