<?php

namespace Modules\Report\Tests\Feature;

use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    public function test_sales_report_renders(): void
    {
        $this->actingAsAdmin()->get(route('reports.sales'))->assertStatus(200);
    }

    public function test_purchase_report_renders(): void
    {
        $this->actingAsAdmin()->get(route('reports.purchase'))->assertStatus(200);
    }

    public function test_inventory_report_renders(): void
    {
        $this->actingAsAdmin()->get(route('reports.inventory'))->assertStatus(200);
    }

    public function test_financial_report_renders(): void
    {
        $this->actingAsAdmin()->get(route('reports.financial'))->assertStatus(200);
    }

    public function test_customer_report_renders(): void
    {
        $this->actingAsAdmin()->get(route('reports.customer'))->assertStatus(200);
    }

    public function test_staff_report_renders(): void
    {
        $this->actingAsAdmin()->get(route('reports.staff'))->assertStatus(200);
    }

    public function test_custom_report_renders(): void
    {
        $this->actingAsAdmin()->get(route('reports.custom'))->assertStatus(200);
    }
}
