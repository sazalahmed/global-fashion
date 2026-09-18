<?php

namespace Modules\Marketing\Tests\Feature;

use Tests\TestCase;

class MarketingControllerTest extends TestCase
{
    public function test_index_renders(): void
    {
        $this->actingAsAdmin()->get(route('marketing.index'))->assertStatus(200);
    }

    public function test_sms_campaigns_renders(): void
    {
        $this->actingAsAdmin()->get(route('marketing.sms-campaigns'))->assertStatus(200);
    }

    public function test_email_campaigns_renders(): void
    {
        $this->actingAsAdmin()->get(route('marketing.email'))->assertStatus(200);
    }

    public function test_loyalty_renders(): void
    {
        $this->actingAsAdmin()->get(route('marketing.loyalty'))->assertStatus(200);
    }
}
