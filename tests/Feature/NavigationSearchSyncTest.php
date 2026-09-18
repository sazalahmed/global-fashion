<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Anti-drift guard for global page search. The sidebar
 * (Modules/Core/resources/views/partials/sidebar.blade.php) is the single
 * source of truth for navigation; every destination it links via route()
 * must also be registered in config/navigation.php so it is searchable.
 * And every config entry must point at a route that actually exists.
 */
class NavigationSearchSyncTest extends TestCase
{
    private const SIDEBAR = 'Modules/Core/resources/views/partials/sidebar.blade.php';

    /**
     * Sidebar route() destinations that are intentionally NOT searchable pages.
     * Keep this list tiny and documented — it is the only sanctioned drift.
     */
    private array $excludedSidebarRoutes = [
        // (none today — every sidebar link is a navigable page)
    ];

    /**
     * Routes that may legitimately be absent in some installs (optional
     * modules). Skipped by BOTH the coverage check and the dead-route check.
     */
    private array $optionalConfigRoutes = [
        'admin.ai-assistant.settings',
    ];

    private function sidebarRouteNames(): array
    {
        $path = base_path(self::SIDEBAR);
        // Guard: a missing/renamed sidebar must FAIL loudly, not silently pass
        // (file_get_contents would return false -> zero matches -> false green).
        $this->assertFileExists($path, 'Sidebar partial not found — update NavigationSearchSyncTest::SIDEBAR');
        $source = file_get_contents($path);

        // Match the bare route('name') helper only: a negative lookbehind rejects
        // identifiers/methods ending in "route" (e.g. request()->routeIs(' has
        // "routeIs(" not "route(", and Route::has(' has a capital R).
        preg_match_all("/(?<![a-zA-Z_\\$])route\\(\\s*'([^']+)'/", $source, $m);

        return array_values(array_unique($m[1]));
    }

    private function configRouteNames(): array
    {
        return array_values(array_unique(array_map(
            fn ($item) => $item['route'],
            config('navigation', [])
        )));
    }

    public function test_every_sidebar_destination_is_registered_for_search(): void
    {
        $configRoutes = $this->configRouteNames();
        $missing = [];

        foreach ($this->sidebarRouteNames() as $route) {
            if (in_array($route, $this->excludedSidebarRoutes, true)) {
                continue;
            }
            if (in_array($route, $this->optionalConfigRoutes, true)) {
                continue;
            }
            if (!in_array($route, $configRoutes, true)) {
                $missing[] = $route;
            }
        }

        $this->assertSame(
            [],
            $missing,
            'Sidebar routes missing from config/navigation.php (add them so they are searchable): '
                . implode(', ', $missing)
        );
    }

    public function test_no_config_navigation_route_is_dead(): void
    {
        $dead = [];

        foreach (config('navigation', []) as $item) {
            $route = $item['route'];
            if (in_array($route, $this->optionalConfigRoutes, true)) {
                continue;
            }
            if (!Route::has($route)) {
                $dead[] = $route;
            }
        }

        $this->assertSame(
            [],
            $dead,
            'config/navigation.php references routes that do not exist: ' . implode(', ', $dead)
        );
    }

    public function test_settings_tab_anchors_exist_in_the_settings_view(): void
    {
        $viewPath = base_path('Modules/Setting/resources/views/index.blade.php');
        $this->assertFileExists($viewPath, 'Settings view not found — update this test path.');
        $view = file_get_contents($viewPath);

        $checked = 0;
        foreach (config('navigation', []) as $item) {
            $anchor = $item['anchor'] ?? null;
            if ($anchor === null) {
                continue;
            }
            $checked++;
            $this->assertStringContainsString(
                'id="' . $anchor . '"',
                $view,
                "Settings tab pane #{$anchor} is referenced in config/navigation.php but missing from the settings view."
            );
            $this->assertStringContainsString(
                'href="#' . $anchor . '"',
                $view,
                "Settings tab trigger for #{$anchor} is referenced in config/navigation.php but missing from the settings view."
            );
        }

        $this->assertGreaterThan(0, $checked, 'Expected at least one settings-tab (anchored) navigation entry.');
    }
}
