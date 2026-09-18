<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\Menu;
use Modules\Ecommerce\Models\MenuItem;

class MenuService
{
    /**
     * Resolved, visibility-filtered, ordered nested tree for a storefront
     * location. Cached per location + auth-state (visibility differs for
     * guest vs. logged-in customers). Live badge counts are intentionally
     * NOT baked in — the widget partial reads session counts at render so
     * cart.js keeps updating them.
     *
     * @return array<int, array>
     */
    public function getTree(string $location, ?Authenticatable $customer = null): array
    {
        $key = $this->cacheKey($location, $customer !== null);

        return Cache::rememberForever($key, function () use ($location, $customer) {
            $menu = Menu::active()->location($location)->with('rootItems')->first();

            if (! $menu) {
                return [];
            }

            return $this->buildTree($menu->rootItems, $customer);
        });
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @return array<int, array>
     */
    protected function buildTree(Collection $items, ?Authenticatable $customer): array
    {
        return $items
            ->filter(fn (MenuItem $item) => $item->is_active && $item->isVisibleTo($customer))
            ->map(fn (MenuItem $item) => [
                'id'        => $item->id,
                'label'     => $item->label,
                'type'      => $item->type,
                'value'     => $item->value,
                'url'       => $item->resolveUrl(),
                'target'    => $item->target ?: '_self',
                'icon'      => $item->icon,
                'css_class' => $item->css_class,
                'settings'  => $item->settings ?? [],
                'children'  => $this->buildTree($item->recursiveChildren, $customer),
            ])
            ->values()
            ->all();
    }

    /**
     * Persist a reordered nested tree: walk the client payload and set each
     * item's parent_id + sort_order. Only items belonging to $menu are
     * touched (guards against cross-menu tampering). One transaction.
     *
     * @param  array<int, array>  $tree  e.g. [['id'=>1,'children'=>[['id'=>2]]], …]
     */
    public function saveTreeOrder(Menu $menu, array $tree): void
    {
        $ownIds = $menu->items()->pluck('id')->all();

        \Illuminate\Support\Facades\DB::transaction(function () use ($tree, $ownIds, $menu) {
            $walk = function (array $nodes, ?int $parentId) use (&$walk, $ownIds, $menu) {
                foreach ($nodes as $order => $node) {
                    $id = (int) ($node['id'] ?? 0);
                    if ($id && in_array($id, $ownIds, true)) {
                        MenuItem::where('id', $id)->where('menu_id', $menu->id)->update([
                            'parent_id'  => $parentId,
                            'sort_order' => $order,
                        ]);
                    }
                    if (! empty($node['children']) && is_array($node['children'])) {
                        $walk($node['children'], $id ?: null);
                    }
                }
            };
            $walk($tree, null);
        });

        $this->forget($menu->location);
    }

    /** Delete every item in a menu (children-first to respect the self-ref FK). */
    public function clearItems(Menu $menu): void
    {
        $menu->items()->whereNotNull('parent_id')->delete();
        $menu->items()->delete();
        $this->forget($menu->location);
    }

    /** Clear + re-seed a single location to its shipped defaults. */
    public function resetToDefaults(string $location): void
    {
        (new \Modules\Ecommerce\Database\Seeders\MenuSeeder())->seedLocation($location);
        $this->forget($location);
    }

    /** Drop both auth-state cache variants for a location. */
    public function forget(string $location): void
    {
        Cache::forget($this->cacheKey($location, true));
        Cache::forget($this->cacheKey($location, false));
    }

    public function forgetAll(): void
    {
        foreach (Menu::LOCATIONS as $location) {
            $this->forget($location);
        }
    }

    protected function cacheKey(string $location, bool $auth): string
    {
        return 'ecommerce.menu.' . $location . '.' . ($auth ? 'auth' : 'guest');
    }
}
