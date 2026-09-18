<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Setting\Services\SettingService;

class NavigationRegistry
{
    /**
     * Search navigation items by term, respecting simple/full mode and
     * permissions. Items pointing at a non-existent route are skipped so a
     * stale entry can never 500 the search endpoint.
     *
     * @return array<int, array>
     */
    public function search(string $term, int $limit = 5): array
    {
        $term = mb_strtolower($term);
        $user = Auth::user();
        $simpleMode = SettingService::isSimpleMode();
        $results = [];

        foreach (static::items() as $item) {
            // Mode filter — mirror the sidebar's simple/full visibility.
            $mode = $item['mode'] ?? 'both';
            if ($mode === 'simple' && !$simpleMode) {
                continue;
            }
            if ($mode === 'full' && $simpleMode) {
                continue;
            }

            // Permission filter — fail closed: a gated page is hidden when there
            // is no user, or the user lacks the permission.
            $permission = $item['permission'] ?? null;
            if ($permission !== null && (!$user || !$user->can($permission))) {
                continue;
            }

            // Dead-route guard — skip optional/missing routes gracefully.
            if (!Route::has($item['route'])) {
                continue;
            }

            // Match label or any keyword.
            $matches = str_contains(mb_strtolower($item['label']), $term);
            if (!$matches) {
                foreach (($item['keywords'] ?? []) as $keyword) {
                    if (str_contains(mb_strtolower($keyword), $term)) {
                        $matches = true;
                        break;
                    }
                }
            }

            if ($matches) {
                $results[] = $item;
                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * All navigable pages — the single source of truth lives in config.
     *
     * @return array<int, array>
     */
    public static function items(): array
    {
        return config('navigation', []);
    }
}
