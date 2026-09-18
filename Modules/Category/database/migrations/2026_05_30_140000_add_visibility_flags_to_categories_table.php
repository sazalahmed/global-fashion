<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storefront visibility flags:
     *  - show_in_menu : appear in the "Browse Categories" navigation dropdown
     *  - show_in_top  : appear in the homepage "Top Categories" section
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'show_in_menu')) {
                $table->boolean('show_in_menu')->default(true)->after('status');
            }
            if (! Schema::hasColumn('categories', 'show_in_top')) {
                $table->boolean('show_in_top')->default(true)->after('show_in_menu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            foreach (['show_in_menu', 'show_in_top'] as $col) {
                if (Schema::hasColumn('categories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
