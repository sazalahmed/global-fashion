<?php

namespace Modules\Dashboard\Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $response = $this->actingAsAdmin()->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('dashboard::index');
    }

    public function test_dashboard_has_kpi_data(): void
    {
        $response = $this->actingAsAdmin()->get(route('dashboard'));
        $response->assertViewHas('kpis');
        $response->assertViewHas('salesTrend');
        $response->assertViewHas('recentSales');
    }

    public function test_unauthenticated_user_redirected(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }
}
