<?php

namespace Modules\Activity\Tests\Feature;

use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('activities.index'))->assertStatus(200);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('activities.index'))->assertRedirect(route('login'));
    }

    public function test_index_returns_html(): void
    {
        $response = $this->actingAsAdmin()->get(route('activities.index'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
    }
}
