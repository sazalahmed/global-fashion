<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->foreignId('blog_category_id')
                ->nullable()
                ->after('author_id')
                ->constrained('blog_categories')
                ->nullOnDelete();
        });

        // Backfill: turn each distinct legacy category string into a managed
        // blog category and link the matching posts to it.
        if (Schema::hasColumn('blog_posts', 'category')) {
            $names = DB::table('blog_posts')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category');

            foreach ($names as $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }

                $slug = $this->uniqueSlug($name);
                $categoryId = DB::table('blog_categories')->insertGetId([
                    'name'       => $name,
                    'slug'       => $slug,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('blog_posts')
                    ->where('category', $name)
                    ->update(['blog_category_id' => $categoryId]);
            }

            Schema::table('blog_posts', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('category')->nullable()->after('author_id');
        });

        // Restore the legacy string values from the linked categories.
        $rows = DB::table('blog_posts')
            ->join('blog_categories', 'blog_posts.blog_category_id', '=', 'blog_categories.id')
            ->select('blog_posts.id', 'blog_categories.name')
            ->get();

        foreach ($rows as $row) {
            DB::table('blog_posts')->where('id', $row->id)->update(['category' => $row->name]);
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blog_category_id');
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;
        while (DB::table('blog_categories')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
};
