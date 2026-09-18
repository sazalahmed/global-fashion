<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('disposal_book_value', 15, 2)->nullable()->after('current_value');
            $table->timestamp('disposed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['disposal_book_value', 'disposed_at']);
        });
    }
};
