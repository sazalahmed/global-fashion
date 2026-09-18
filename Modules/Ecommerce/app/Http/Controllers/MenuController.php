<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Http\Requests\StoreMenuItemRequest;
use Modules\Ecommerce\Http\Requests\UpdateMenuItemRequest;
use Modules\Ecommerce\Models\Menu;
use Modules\Ecommerce\Models\MenuItem;
use Modules\Ecommerce\Models\Page;
use Modules\Ecommerce\Services\MenuService;

class MenuController extends Controller
{
    public function __construct(protected MenuService $service) {}

    /** List the storefront menu locations. */
    public function index(): View
    {
        bpAuthorize('ecommerce.view');
        $labels = [
            'header_main'   => 'Main Menu (Desktop Header)',
            'footer'        => 'Footer',
            'mobile_drawer' => 'Mobile Drawer',
            'mobile_bottom' => 'Mobile Bottom Bar',
        ];

        // Ensure all known locations exist so each is always editable.
        foreach (Menu::LOCATIONS as $location) {
            Menu::firstOrCreate(['location' => $location], ['name' => $labels[$location] ?? $location, 'is_active' => true]);
        }

        $menus = Menu::withCount('items')->orderBy('id')->get();

        return view('ecommerce::menus.index', compact('menus', 'labels'));
    }

    /** Builder UI for one location. */
    public function edit(Menu $menu): View
    {
        bpAuthorize('ecommerce.edit');
        $menu->load('rootItems');
        $categories = Category::active()->ordered()->get(['id', 'name']);
        $routes     = MenuItem::menuRouteOptions();
        $widgets    = MenuItem::WIDGETS;
        $pages      = Page::published()->orderBy('title')->get(['id', 'title']);

        return view('ecommerce::menus.builder', compact('menu', 'categories', 'routes', 'widgets', 'pages'));
    }

    public function storeItem(StoreMenuItemRequest $request, Menu $menu): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $data = $request->validated();
        $data['sort_order'] = ((int) $menu->items()->max('sort_order')) + 1;

        $menu->items()->create($data);
        $this->service->forget($menu->location);

        return back()->with('success', __('Menu item added.'));
    }

    public function updateItem(UpdateMenuItemRequest $request, MenuItem $item): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $item->update($request->validated());
        $this->service->forget($item->menu->location);

        return back()->with('success', __('Menu item updated.'));
    }

    public function destroyItem(MenuItem $item): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $location = $item->menu->location;
        $item->delete(); // deleting hook removes the whole subtree
        $this->service->forget($location);

        return back()->with('success', __('Menu item deleted.'));
    }

    public function reorder(Request $request, Menu $menu): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $validated = $request->validate(['tree' => 'present|array']);
        $this->service->saveTreeOrder($menu, $validated['tree']);

        return response()->json(['success' => true]);
    }

    public function toggleItem(MenuItem $item): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $item->update(['is_active' => ! $item->is_active]);
        $this->service->forget($item->menu->location);

        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    public function toggleStatus(Menu $menu): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $menu->update(['is_active' => ! $menu->is_active]);
        $this->service->forget($menu->location);

        return response()->json(['success' => true, 'is_active' => $menu->is_active, 'message' => __('Status updated.')]);
    }

    public function clear(Menu $menu): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->clearItems($menu);

        return back()->with('success', __('All items removed from this menu.'));
    }

    public function reset(Menu $menu): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->resetToDefaults($menu->location);

        return back()->with('success', __('Menu reset to default items.'));
    }
}
