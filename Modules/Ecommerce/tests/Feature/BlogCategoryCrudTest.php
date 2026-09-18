<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\User;
use Modules\Ecommerce\Models\BlogCategory;
use Modules\Ecommerce\Models\BlogPost;
use Tests\TestCase;

class BlogCategoryCrudTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('ecommerce.blog-categories'))
            ->assertStatus(200);
    }

    public function test_store_creates_category_and_auto_slugs(): void
    {
        $this->actingAs($this->admin())
            ->post(route('ecommerce.blog-categories.store'), ['name' => 'Fashion Tips'])
            ->assertRedirect(route('ecommerce.blog-categories'));

        $this->assertDatabaseHas('blog_categories', [
            'name' => 'Fashion Tips',
            'slug' => 'fashion-tips',
            'is_active' => true,
        ]);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('ecommerce.blog-categories.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_update_modifies_category(): void
    {
        $cat = BlogCategory::create(['name' => 'Old', 'slug' => 'old']);

        $this->actingAs($this->admin())
            ->put(route('ecommerce.blog-categories.update', $cat), [
                'name' => 'New Name', 'slug' => 'new-name', 'is_active' => 0,
            ])
            ->assertRedirect(route('ecommerce.blog-categories'));

        $this->assertDatabaseHas('blog_categories', [
            'id' => $cat->id, 'name' => 'New Name', 'slug' => 'new-name', 'is_active' => false,
        ]);
    }

    public function test_destroy_soft_deletes_category(): void
    {
        $cat = BlogCategory::create(['name' => 'Delete Me', 'slug' => 'delete-me']);

        $this->actingAs($this->admin())
            ->delete(route('ecommerce.blog-categories.destroy', $cat))
            ->assertRedirect(route('ecommerce.blog-categories'));

        $this->assertSoftDeleted('blog_categories', ['id' => $cat->id]);
    }

    public function test_blog_post_can_be_assigned_a_category(): void
    {
        $cat = BlogCategory::create(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($this->admin())
            ->post(route('ecommerce.blog.store'), [
                'title' => 'Hello World',
                'blog_category_id' => $cat->id,
                'is_published' => 1,
                'published_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('ecommerce.blog'));

        $post = BlogPost::where('title', 'Hello World')->first();
        $this->assertNotNull($post);
        $this->assertSame($cat->id, $post->blog_category_id);
        $this->assertSame('News', $post->category->name);
    }

    private function makePost(array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'title'        => 'Sample Post',
            'slug'         => 'sample-post',
            'content'      => 'Body',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    public function test_post_in_active_category_is_visible(): void
    {
        $cat = BlogCategory::create(['name' => 'Active', 'slug' => 'active', 'is_active' => true]);
        $this->makePost(['title' => 'Visible One', 'slug' => 'visible-one', 'blog_category_id' => $cat->id]);

        $this->get(route('storefront.blog.index'))->assertStatus(200)->assertSee('Visible One');
        $this->get(route('storefront.blog.show', 'visible-one'))->assertStatus(200);
    }

    public function test_post_in_inactive_category_is_hidden_from_index_and_404s(): void
    {
        $cat = BlogCategory::create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);
        $this->makePost(['title' => 'Hidden One', 'slug' => 'hidden-one', 'blog_category_id' => $cat->id]);

        $this->get(route('storefront.blog.index'))->assertStatus(200)->assertDontSee('Hidden One');
        $this->get(route('storefront.blog.show', 'hidden-one'))->assertStatus(404);
    }

    public function test_uncategorised_post_stays_visible(): void
    {
        $this->makePost(['title' => 'No Cat', 'slug' => 'no-cat', 'blog_category_id' => null]);

        $this->get(route('storefront.blog.index'))->assertStatus(200)->assertSee('No Cat');
        $this->get(route('storefront.blog.show', 'no-cat'))->assertStatus(200);
    }
}
