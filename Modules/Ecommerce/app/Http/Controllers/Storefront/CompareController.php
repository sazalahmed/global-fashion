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

class CompareController extends Controller
{
    /**
     * Maximum number of items that can be compared at once. Keeps the
     * side-by-side table readable on smaller screens. Products and combos
     * share this budget since they sit in the same comparison table.
     */
    private const MAX_ITEMS = 5;

    /**
     * Display the compare page (side-by-side product + combo comparison).
     */
    public function index(ComboService $comboService): View
    {
        $compare  = session('compare', []);
        $products = collect();

        if (!empty($compare)) {
            // Preserve the order items were added in (whereIn ignores it).
            $products = Product::storefrontVisible()
                ->whereIn('id', $compare)
                ->with(['images', 'category', 'brand', 'approvedReviews'])
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $compare))
                ->values();
        }

        // Combos kept in a separate session key to avoid product-id collisions.
        $comboIds = session('compare_combos', []);
        $combos   = collect();
        if (!empty($comboIds)) {
            $combos = Combo::active()
                ->whereIn('id', $comboIds)
                ->with(['items.product', 'items.variant', 'categories'])
                ->get()
                ->sortBy(fn ($c) => array_search($c->id, $comboIds))
                ->values();
        }

        $cartItems = session('cart', []);

        return view('ecommerce::storefront.pages.compare.index', compact(
            'products',
            'combos',
            'comboService',
            'cartItems'
        ) + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow')]);
    }

    /**
     * Add a product or combo to the compare list.
     */
    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_id' => 'required_without:combo_id|integer|exists:products,id',
            'combo_id'   => 'required_without:product_id|integer|exists:combos,id',
        ]);

        $added = false;

        if ($request->filled('combo_id')) {
            $comboId = (int) $request->input('combo_id');
            $combos  = session('compare_combos', []);

            if (in_array($comboId, $combos, true)) {
                return $this->respond($request, true, 'Already in compare list.', false);
            }
            if ($this->compareCount() >= self::MAX_ITEMS) {
                return $this->respond($request, false, 'You can compare up to ' . self::MAX_ITEMS . ' items.', false);
            }
            $combos[] = $comboId;
            session(['compare_combos' => $combos]);
            $added = true;
        } else {
            $productId = (int) $request->input('product_id');
            $compare   = session('compare', []);

            if (in_array($productId, $compare, true)) {
                return $this->respond($request, true, 'Already in compare list.', false);
            }
            if ($this->compareCount() >= self::MAX_ITEMS) {
                return $this->respond($request, false, 'You can compare up to ' . self::MAX_ITEMS . ' items.', false);
            }
            $compare[] = $productId;
            session(['compare' => $compare]);
            $added = true;
        }

        return $this->respond($request, true, 'Added to compare.', $added);
    }

    /**
     * Remove a product or combo from the compare list.
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
                session('compare_combos', []),
                fn ($id) => (int) $id !== $comboId
            ));
            session(['compare_combos' => $combos]);
        } else {
            $productId = (int) $request->input('product_id');
            $compare   = array_values(array_filter(
                session('compare', []),
                fn ($id) => (int) $id !== $productId
            ));
            session(['compare' => $compare]);
        }

        return $this->respond($request, true, 'Removed from compare.');
    }

    /** Combined product + combo compare size. */
    private function compareCount(): int
    {
        return count((array) session('compare', []))
            + count((array) session('compare_combos', []));
    }

    /**
     * Shared JSON / redirect response shape for the compare actions.
     */
    private function respond(Request $request, bool $success, string $message, bool $added = false): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'       => $success,
                'message'       => $message,
                'added'         => $added,
                'compare_count' => $this->compareCount(),
            ], $success ? 200 : 422);
        }

        return redirect()->back()->with($success ? 'success' : 'error', __($message));
    }
}
