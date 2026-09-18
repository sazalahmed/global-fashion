<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Product\Models\Product;
use Modules\Variant\Models\ProductVariant;

class CartController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    /**
     * Display the cart page.
     */
    public function index(): View
    {
        $cart      = session('cart', []);
        $subtotal  = $this->storefrontService->calculateCartSubtotal($cart);
        $coupon    = session('coupon');
        $discount  = $coupon ? ($coupon['discount'] ?? 0) : 0;
        $cartItems = $cart;

        // The cart view (and its Billing Summary) renders $cartSubtotal /
        // $cartTotal. Shipping is determined at checkout, so it is 0 here.
        $shipping     = 0;
        $cartSubtotal = $subtotal;
        $cartTotal    = max(0, $subtotal - $discount + $shipping);

        return view('ecommerce::storefront.pages.cart.index', compact(
            'cart',
            'subtotal',
            'coupon',
            'discount',
            'cartItems',
            'shipping',
            'cartSubtotal',
            'cartTotal'
        ) + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow')]);
    }

    /**
     * Add a product to the cart.
     */
    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
            'variant_id' => 'nullable|integer',
        ]);

        $product = Product::storefrontVisible()
            ->findOrFail($request->input('product_id'));

        // Respect the product's stock settings: a product that tracks stock,
        // disallows negative stock, and has no available quantity cannot be
        // added to the cart or bought. (is_in_stock mirrors scopeInStock.)
        if (! $product->is_in_stock) {
            $message = __('This product is out of stock.');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $quantity  = $request->input('quantity', 1);
        $variantId = $request->input('variant_id');
        $cartKey   = $variantId ? $product->id . '-' . $variantId : (string) $product->id;

        $effectivePrice = $this->storefrontService->calculateEffectivePrice($product);

        // If variant selected, use variant price and build variant name
        $variantName = null;
        $variantAttributes = [];
        if ($variantId) {
            $variant = ProductVariant::with('attributeValues.attribute')->active()->find($variantId);

            // Never trust the posted variant id: it must be an ACTIVE variant
            // of this product. Reject inactive/removed/mismatched variants so
            // a hidden variant can't be added to the cart.
            if (! $variant || $variant->product_id !== $product->id) {
                $message = __('The selected variant is no longer available.');
                return $request->ajax() || $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $message], 422)
                    : redirect()->back()->with('error', $message);
            }

            // Per-variant stock guard: product-level is_in_stock can be true
            // (a different variant has stock) while THIS variant is sold out.
            // Block it unless the product doesn't track stock or allows backorder.
            if ($product->track_stock && ! $product->allow_negative_stock && $variant->total_stock <= 0) {
                $message = __('The selected variant is out of stock.');
                return $request->ajax() || $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $message], 422)
                    : redirect()->back()->with('error', $message);
            }

            // Apply the product's discount/campaign/flash-deal to the
            // variant's own price so each variant is discounted individually.
            $effectivePrice = $this->storefrontService->calculateEffectivePrice($product, $variant);
            $variantName = $variant->variant_name;

            // Per-attribute breakdown (e.g. Color: Red, Size: XL) so the
            // mini-cart / cart can render labelled rows instead of a single
            // combined string.
            $variantAttributes = $variant->attributeValues
                ->filter(fn ($av) => $av->attribute)
                ->sortBy(fn ($av) => $av->attribute->sort_order)
                ->map(fn ($av) => [
                    'label' => $av->attribute->base_name,
                    'value' => $av->value,
                ])
                ->values()
                ->all();
        } elseif ($product->isVariable()) {
            // A variable product can only be added with a chosen variant.
            $message = __('Please select a variant before adding to cart.');
            return $request->ajax() || $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        $cart = session('cart', []);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id'         => $product->id,
                'variant_id'         => $variantId,
                'variant_name'       => $variantName,
                'variant_attributes' => $variantAttributes,
                'name'               => $product->name,
                'slug'               => $product->slug,
                'price'              => $effectivePrice,
                'sell_price'         => (float) $product->sell_price,
                'image'              => $product->image,
                'quantity'           => $quantity,
                'sku'                => $product->sku,
            ];
        }

        session(['cart' => $cart]);

        // Remove coupon when cart changes
        $this->recalculateCoupon($cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Product added to cart.',
                'cart_count'     => $this->getCartCount($cart),
                'cart_total'     => $this->storefrontService->calculateCartSubtotal($cart),
                'mini_cart_html' => $this->renderMiniCart($cart),
            ]);
        }

        return redirect()->back()->with('success', __('Product added to cart.'));
    }

    /**
     * Add a combo package to the cart as a single line. The combo expands into
     * its component products at order creation (see StorefrontService::createOrder).
     * The price is always recomputed from the DB — never trust a client price.
     */
    public function addCombo(Request $request, ComboService $combos): JsonResponse|RedirectResponse
    {
        $request->validate([
            'combo_id'   => 'required|integer|exists:combos,id',
            'quantity'   => 'nullable|integer|min:1',
            'combo_size' => 'nullable|string|max:50',
        ]);

        $combo = Combo::active()
            ->with(['items.product.variants.attributeValues.attribute', 'items.variant.attributeValues.attribute'])
            ->findOrFail($request->integer('combo_id'));
        $quantity = max(1, (int) $request->input('quantity', 1));

        $reject = function (string $message) use ($request) {
            return $request->ajax() || $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->back()->with('error', $message);
        };

        // Size-required combos: validate the chosen size is offered and in stock.
        $size = null;
        if ($combo->size_required) {
            $size = trim((string) $request->input('combo_size', ''));
            $match = collect($combos->availableSizes($combo))->firstWhere('value', $size);
            if ($size === '' || ! $match) {
                return $reject(__('Please select a valid size.'));
            }
            if (! $combos->isInStockForSize($combo, $size, $quantity)) {
                return $reject(__('This size is out of stock.'));
            }
        } elseif (! $combos->isInStock($combo, $quantity)) {
            return $reject(__('This combo is out of stock.'));
        }

        $components = $combo->items->map(function ($item) use ($combos, $size) {
            $variant = $size ? $combos->resolveComponentVariant($item, $size) : $item->variant;
            return [
                'product_id'   => $item->product_id,
                'variant_id'   => $variant?->id ?? $item->variant_id,
                'quantity'     => $item->quantity,
                'name'         => $item->product->name ?? __('Product'),
                'variant_name' => $size ?: $item->variant?->variant_name,
            ];
        })->all();

        $cart = session('cart', []);
        $key  = 'combo:' . $combo->id . ($size ? ':' . $size : '');
        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'type'       => 'combo',
                'combo_id'   => $combo->id,
                'name'       => $combo->name,
                'slug'       => $combo->slug,
                'thumbnail'  => $combo->thumbnail,
                'price'      => $combos->effectivePrice($combo),
                'quantity'   => $quantity,
                'size'       => $size,
                'components' => $components,
            ];
        }

        session(['cart' => $cart]);
        $this->recalculateCoupon($cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Combo added to cart.',
                'cart_count'     => $this->getCartCount($cart),
                'cart_total'     => $this->storefrontService->calculateCartSubtotal($cart),
                'mini_cart_html' => $this->renderMiniCart($cart),
            ]);
        }

        return redirect()->back()->with('success', __('Combo added to cart.'));
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'cart_key' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session('cart', []);
        $cartKey = $request->input('cart_key');

        if (!isset($cart[$cartKey])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Item not found in cart.'], 404);
            }
            return redirect()->back()->with('error', __('Item not found in cart.'));
        }

        $cart[$cartKey]['quantity'] = $request->input('quantity');
        session(['cart' => $cart]);

        $this->recalculateCoupon($cart);

        $subtotal = $this->storefrontService->calculateCartSubtotal($cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Cart updated.',
                'cart_count'     => $this->getCartCount($cart),
                'cart_total'     => $subtotal,
                'item_total'     => round($cart[$cartKey]['price'] * $cart[$cartKey]['quantity'], 2),
                'coupon'         => session('coupon'),
                'mini_cart_html' => $this->renderMiniCart($cart),
            ]);
        }

        return redirect()->back()->with('success', __('Cart updated.'));
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'cart_key' => 'required|string',
        ]);

        $cart = session('cart', []);
        $cartKey = $request->input('cart_key');

        unset($cart[$cartKey]);
        session(['cart' => $cart]);

        $this->recalculateCoupon($cart);

        $subtotal = $this->storefrontService->calculateCartSubtotal($cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Item removed from cart.',
                'cart_count'     => $this->getCartCount($cart),
                'cart_total'     => $subtotal,
                'coupon'         => session('coupon'),
                'mini_cart_html' => $this->renderMiniCart($cart),
            ]);
        }

        return redirect()->back()->with('success', __('Item removed from cart.'));
    }

    /**
     * Remove multiple selected items from the cart (multi-select bulk action).
     */
    public function removeSelected(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'cart_keys'   => 'required|array|min:1',
            'cart_keys.*' => 'string',
        ]);

        $cart = session('cart', []);

        foreach ($request->input('cart_keys') as $cartKey) {
            unset($cart[$cartKey]);
        }

        session(['cart' => $cart]);

        $this->recalculateCoupon($cart);

        $subtotal = $this->storefrontService->calculateCartSubtotal($cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Selected items removed.',
                'cart_count'     => $this->getCartCount($cart),
                'cart_total'     => $subtotal,
                'coupon'         => session('coupon'),
                'mini_cart_html' => $this->renderMiniCart($cart),
            ]);
        }

        return redirect()->back()->with('success', __('Selected items removed.'));
    }

    /**
     * Clear the entire cart.
     */
    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        session()->forget(['cart', 'coupon']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Cart cleared.',
                'cart_count'     => 0,
                'cart_total'     => 0,
                'mini_cart_html' => $this->renderMiniCart([]),
            ]);
        }

        return redirect()->back()->with('success', __('Cart cleared.'));
    }

    /**
     * Apply a coupon code to the cart.
     */
    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $cart = session('cart', []);
        $subtotal = $this->storefrontService->calculateCartSubtotal($cart);

        if ($subtotal <= 0) {
            return $this->couponResponse($request, false, 'Your cart is empty.');
        }

        $coupon = $this->storefrontService->findValidCoupon(
            $request->input('coupon_code'),
            $subtotal
        );

        if (!$coupon) {
            return $this->couponResponse($request, false, 'Invalid or expired coupon code.');
        }

        $discount = $coupon->calculateDiscount($subtotal);

        session(['coupon' => [
            'code'     => $coupon->code,
            'name'     => $coupon->name,
            'type'     => $coupon->type,
            'value'    => (float) $coupon->value,
            'discount' => $discount,
        ]]);

        return $this->couponResponse($request, true, 'Coupon applied successfully.', [
            'coupon'   => session('coupon'),
            'discount' => $discount,
        ]);
    }

    /**
     * Remove the applied coupon.
     */
    public function removeCoupon(Request $request): JsonResponse|RedirectResponse
    {
        session()->forget('coupon');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Coupon removed.',
            ]);
        }

        return redirect()->back()->with('success', __('Coupon removed.'));
    }

    // ── Private Helpers ──

    /**
     * Get total item count in the cart.
     */
    private function getCartCount(array $cart): int
    {
        $count = 0;

        foreach ($cart as $item) {
            $count += $item['quantity'] ?? 0;
        }

        return $count;
    }

    /**
     * Render the mini-cart drawer body as HTML so AJAX endpoints can return
     * an updated snapshot for the right-side offcanvas.
     */
    private function renderMiniCart(array $cart): string
    {
        return view('ecommerce::storefront.partials.mini-cart-items', ['cartItems' => $cart])->render();
    }

    /**
     * Recalculate coupon discount when cart changes.
     */
    private function recalculateCoupon(array $cart): void
    {
        $couponData = session('coupon');

        if (!$couponData) {
            return;
        }

        $subtotal = $this->storefrontService->calculateCartSubtotal($cart);

        if ($subtotal <= 0) {
            session()->forget('coupon');
            return;
        }

        $coupon = $this->storefrontService->findValidCoupon($couponData['code'], $subtotal);

        if (!$coupon) {
            session()->forget('coupon');
            return;
        }

        $discount = $coupon->calculateDiscount($subtotal);
        $couponData['discount'] = $discount;
        session(['coupon' => $couponData]);
    }

    /**
     * Build coupon response for AJAX or redirect.
     */
    private function couponResponse(Request $request, bool $success, string $message, array $extra = []): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(array_merge([
                'success' => $success,
                'message' => $message,
            ], $extra), $success ? 200 : 422);
        }

        $flashKey = $success ? 'success' : 'error';
        return redirect()->back()->with($flashKey, $message);
    }
}
