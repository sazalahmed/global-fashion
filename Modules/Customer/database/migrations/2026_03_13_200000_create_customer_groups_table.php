<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
        });

        // Seed the four canonical groups used by the rest of the app.
        $now = now();
        DB::table('customer_groups')->insertOrIgnore([
            ['name' => 'Retail',    'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wholesale', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'VIP',       'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Corporate', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
