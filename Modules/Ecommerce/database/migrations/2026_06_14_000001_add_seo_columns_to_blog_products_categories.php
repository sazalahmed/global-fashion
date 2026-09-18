<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('seo_title', 255)->nullable()->after('slug');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->string('seo_image')->nullable()->after('seo_description');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->string('seo_image')->nullable()->after('seo_description');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->string('meta_image')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', fn (Blueprint $t) => $t->dropColumn(['seo_title', 'seo_description', 'seo_image']));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('seo_image'));
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('meta_image'));
    }
};
