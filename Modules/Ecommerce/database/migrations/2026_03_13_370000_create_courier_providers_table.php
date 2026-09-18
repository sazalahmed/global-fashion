<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('store_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default providers
        $providers = [
            ['name' => 'Pathao Courier', 'slug' => 'pathao', 'sort_order' => 1],
            ['name' => 'Steadfast', 'slug' => 'steadfast', 'sort_order' => 2],
            ['name' => 'RedX', 'slug' => 'redx', 'sort_order' => 3],
            ['name' => 'eCourier', 'slug' => 'ecourier', 'sort_order' => 4],
            ['name' => 'Paperfly', 'slug' => 'paperfly', 'sort_order' => 5],
            ['name' => 'Sundarban Courier', 'slug' => 'sundarban', 'sort_order' => 6],
            ['name' => 'SA Paribahan', 'slug' => 'sa_paribahan', 'sort_order' => 7],
        ];

        $now = now();
        foreach ($providers as $provider) {
            DB::table('courier_providers')->insert(array_merge($provider, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_providers');
    }
};
