<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Search\NavigationRegistry;
use Tests\TestCase;

/**
 * Behavior of the config-driven navigation search: coverage of the Income
 * page, simple/full mode filtering, and that results carry the config item
 * shape (route + anchor) for the result builder.
 *
 * @todo SettingService::getAccountingMode() is hardcoded to 'simple', so the
 *       complementary direction (full-mode pages SHOWN when in full mode)
 *       cannot be tested yet. Add that case once mode is wired to settings.
 */
class NavigationRegistrySearchTest extends TestCase
{
    private function search(string $term): array
    {
        $this->actingAs(User::factory()->create());

        return app(NavigationRegistry::class)->search($term, 20);
    }

    public function test_income_page_is_searchable(): void
    {
        // SettingService::isSimpleMode() is true by default, so the simple-mode
        // "Income" page must surface.
        $routes = array_column($this->search('income'), 'route');

        $this->assertContains('money.income', $routes);
    }

    public function test_full_mode_pages_are_hidden_in_simple_mode(): void
    {
        // 'Journal Entries' is full-mode only; in simple mode it must not appear.
        $routes = array_column($this->search('journal'), 'route');

        $this->assertNotContains('accounting.journal-entries', $routes);
    }

    public function test_settings_tab_items_carry_their_anchor(): void
    {
        $tax = collect($this->search('vat'))
            ->firstWhere('route', 'settings.index');

        $this->assertNotNull($tax, 'Expected a settings.index result for "vat"');
        $this->assertSame('taxSettings', $tax['anchor'] ?? null);
    }

    public function test_builder_appends_anchor_to_settings_tab_url(): void
    {
        $builder = app(\App\Services\Search\SearchResultBuilder::class);

        $result = $builder->buildNavigation([
            'label'  => 'Tax / VAT',
            'route'  => 'settings.index',
            'icon'   => 'fa-percent',
            'group'  => 'Settings',
            'anchor' => 'taxSettings',
        ], 'vat');

        $this->assertStringEndsWith('#taxSettings', $result['url']);
    }

    public function test_builder_omits_anchor_when_absent(): void
    {
        $builder = app(\App\Services\Search\SearchResultBuilder::class);

        $result = $builder->buildNavigation([
            'label'  => 'Income',
            'route'  => 'money.income',
            'icon'   => 'fa-arrow-trend-up',
            'group'  => 'Finance',
            'anchor' => null,
        ], 'income');

        $this->assertStringNotContainsString('#', $result['url']);
    }
}
