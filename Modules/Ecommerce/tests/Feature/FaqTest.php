<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Faq;
use Tests\TestCase;

class FaqTest extends TestCase
{
    public function test_active_and_ordered_scopes(): void
    {
        Faq::create(['question' => 'Q3', 'answer' => 'A3', 'position' => 3, 'is_active' => true]);
        Faq::create(['question' => 'Q1', 'answer' => 'A1', 'position' => 1, 'is_active' => true]);
        Faq::create(['question' => 'Q hidden', 'answer' => 'A', 'position' => 2, 'is_active' => false]);

        $result = Faq::active()->ordered()->pluck('question')->all();

        $this->assertSame(['Q1', 'Q3'], $result);
    }

    public function test_admin_can_create_faq(): void
    {
        $response = $this->actingAs($this->admin)->post(route('ecommerce.faqs.store'), [
            'question'  => 'How do I track my order?',
            'answer'    => 'Visit My Account → Orders.',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('ecommerce.faqs.index'));
        $this->assertDatabaseHas('faqs', ['question' => 'How do I track my order?']);
    }

    public function test_create_requires_question_and_answer(): void
    {
        $this->actingAs($this->admin)->post(route('ecommerce.faqs.store'), [])
            ->assertSessionHasErrors(['question', 'answer']);
    }

    public function test_admin_can_reorder_faqs(): void
    {
        $a = Faq::create(['question' => 'A', 'answer' => 'a', 'position' => 1]);
        $b = Faq::create(['question' => 'B', 'answer' => 'b', 'position' => 2]);

        $this->actingAs($this->admin)->post(route('ecommerce.faqs.reorder'), [
            'ordered_ids' => [$b->id, $a->id],
        ])->assertOk();

        $this->assertSame(1, $b->fresh()->position);
        $this->assertSame(2, $a->fresh()->position);
    }

    public function test_storefront_faq_page_shows_active_faqs_in_order(): void
    {
        Faq::create(['question' => 'Second Q', 'answer' => 'A2', 'position' => 2, 'is_active' => true]);
        Faq::create(['question' => 'First Q', 'answer' => 'A1', 'position' => 1, 'is_active' => true]);
        Faq::create(['question' => 'Hidden Q', 'answer' => 'A', 'position' => 3, 'is_active' => false]);

        $response = $this->get(route('storefront.faq.index'));

        $response->assertOk()->assertSee('First Q')->assertSee('Second Q')->assertDontSee('Hidden Q');
        $response->assertSeeInOrder(['First Q', 'Second Q']);
    }
}
