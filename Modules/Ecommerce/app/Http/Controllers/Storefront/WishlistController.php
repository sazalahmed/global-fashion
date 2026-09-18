<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Product\Models\Product;

class WishlistController extends Controller
{
    /**
     * Display the wishlist page.
     */
    public function index(ComboService $comboService): View
    {
        $wishlist = session('wishlist', []);
        $products = collect();

        if (!empty($wishlist)) {
            $products = Product::storefrontVisible()
                ->whereIn('id', $wishlist)
                ->with(['images', 'category', 'brand'])
                ->get();
        }

        // Combos live in a separate session key so their ids never collide with
        // product ids (a combo #15 and a product #15 are different things).
        $comboIds = session('wishlist_combos', []);
        $combos = collect();
        if (!empty($comboIds)) {
            $combos = Combo::active()
                ->whereIn('id', $comboIds)
                ->with(['items.product', 'items.variant'])
                ->get();
        }

        $cartItems = session('cart', []);

        return view('ecommerce::storefront.pages.wishlist.index', compact(
            'products',
            'combos',
            'comboService',
            'cartItems'
        ) + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow')]);
    }

    /**
     * Add a product or combo to the wishlist.
     */
    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_id' => 'required_without:combo_id|integer|exists:products,id',
            'combo_id'   => 'required_without:product_id|integer|exists:combos,id',
        ]);

        if ($request->filled('combo_id')) {
            $comboId = (int) $request->input('combo_id');
            $combos  = session('wishlist_combos', []);
            if (!in_array($comboId, $combos, true)) {
                $combos[] = $comboId;
                session(['wishlist_combos' => $combos]);
            }
        } else {
            $productId = (int) $request->input('product_id');
            $wishlist  = session('wishlist', []);
            if (!in_array($productId, $wishlist, true)) {
                $wishlist[] = $productId;
                session(['wishlist' => $wishlist]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Added to wishlist.',
                'wishlist_count' => $this->wishlistCount(),
            ]);
        }

        return redirect()->back()->with('success', __('Added to wishlist.'));
    }

    /**
     * Remove a product or combo from the wishlist.
     */
    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_id' => 'required_without:combo_id|integer',
            'combo_id'   => 'required_without:product_id|integer',
        ]);

        if ($request->filled('combo_id')) {
            $comboId = (int) $request->input('combo_id');
            $combos  = array_values(array_filter(
                session('wishlist_combos', []),
                fn ($id) => (int) $id !== $comboId
            ));
            session(['wishlist_combos' => $combos]);
        } else {
            $productId = (int) $request->input('product_id');
            $wishlist  = array_values(array_filter(
                session('wishlist', []),
                fn ($id) => (int) $id !== $productId
            ));
            session(['wishlist' => $wishlist]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Removed from wishlist.',
                'wishlist_count' => $this->wishlistCount(),
            ]);
        }

        return redirect()->back()->with('success', __('Removed from wishlist.'));
    }

    /** Combined product + combo wishlist size, used for the header badge. */
    private function wishlistCount(): int
    {
        return count((array) session('wishlist', []))
            + count((array) session('wishlist_combos', []));
    }
}
