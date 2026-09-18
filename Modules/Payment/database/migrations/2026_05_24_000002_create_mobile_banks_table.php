<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mobile_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the default BD mobile banking providers so existing
        // payment_accounts.mobile_bank_name values keep matching.
        DB::table('mobile_banks')->insert([
            ['name' => 'bKash',     'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nagad',     'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rocket',    'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Upay',      'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SureCash',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_banks');
    }
};
