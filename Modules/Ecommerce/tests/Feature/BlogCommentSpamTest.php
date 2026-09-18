<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\BlogPost;
use Tests\TestCase;

class BlogCommentSpamTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Focus these tests on the anti-bot checks, not the rate limiter.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    private function makePost(): BlogPost
    {
        return BlogPost::create([
            'title'        => 'Spam Target',
            'slug'         => 'spam-target',
            'content'      => 'Body',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'            => 'Real Person',
            'email'           => 'person@example.com',
            'comment'         => 'This is a genuine, thoughtful comment.',
            'website'         => '',                                         // honeypot empty
            'form_started_at' => encrypt(now()->subSeconds(10)->timestamp),  // far enough in the past
        ], $overrides);
    }

    public function test_genuine_comment_is_accepted_and_held_for_moderation(): void
    {
        $post = $this->makePost();

        $this->post(route('storefront.blog.comment.store', $post->slug), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('blog_comments', [
            'blog_post_id' => $post->id,
            'email'        => 'person@example.com',
            'is_approved'  => false,
        ]);
    }

    public function test_honeypot_filled_is_rejected(): void
    {
        $post = $this->makePost();

        $this->post(route('storefront.blog.comment.store', $post->slug), $this->payload([
            'website' => 'http://spam.example',
        ]))->assertSessionHasErrors();

        $this->assertDatabaseCount('blog_comments', 0);
    }

    public function test_too_fast_submission_is_rejected(): void
    {
        $post = $this->makePost();

        $this->post(route('storefront.blog.comment.store', $post->slug), $this->payload([
            'form_started_at' => encrypt(now()->timestamp), // ~0s elapsed
        ]))->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('blog_comments', 0);
    }

    public function test_missing_or_tampered_timestamp_is_rejected(): void
    {
        $post = $this->makePost();

        $this->post(route('storefront.blog.comment.store', $post->slug), $this->payload([
            'form_started_at' => 'tampered-value',
        ]))->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('blog_comments', 0);
    }
}
