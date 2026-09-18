<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            // Self-reference. nullOnDelete (NOT cascade) — MySQL self-referencing
            // ON DELETE CASCADE is unreliable for multi-level trees; subtree
            // deletion is handled in the MenuItem model's `deleting` hook.
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();

            $table->string('label');                       // display text (also used for headings)
            $table->string('type')->default('url');        // route|category|url|heading|categories_dropdown|widget
            $table->string('value')->nullable();           // route name | category id | URL | widget key
            $table->string('target')->default('_self');    // _self | _blank
            $table->string('icon')->nullable();            // FontAwesome class or asset key
            $table->string('css_class')->nullable();       // optional extra classes
            $table->string('visibility')->default('all');  // all | guest | auth
            $table->json('settings')->nullable();          // per-item extras (e.g. categories_dropdown limit)
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
