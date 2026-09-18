<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['shareholder', 'investor'])->index();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('nid_or_tin', 50)->nullable();
            $table->date('join_date');

            // Shareholder-only — share count drives auto % across all shareholders
            $table->decimal('shares_owned', 15, 2)->nullable();

            // Pure-investor-only — contractual % off net profit
            $table->decimal('profit_share_pct', 5, 2)->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};
