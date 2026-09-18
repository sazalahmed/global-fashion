<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Location\Models\District;
use Modules\Ecommerce\Services\FraudCheckService;
use Modules\Ecommerce\Services\StorefrontService;

class CheckoutController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService,
        protected FraudCheckService $fraudCheckService,
        protected \Modules\Ecommerce\Services\IncompleteCheckoutService $incompleteCheckouts
    ) {}

    /**
     * Best-effort abandoned-checkout capture (AJAX, debounced from the
     * checkout form). Stores the shopper's contact info + session cart as an
     * incomplete order so staff can follow up if they never place the order.
     * Always answers 200 — a capture failure must never disturb checkout.
     */
    public function capture(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'idempotency_token' => 'required|string|max:64',
            'customer_name'     => 'required|string|max:255',
            'customer_phone'    => ['required', 'string', 'max:20', new \App\Rules\PhoneNumber],
            'customer_email'    => 'nullable|email|max:255',
            'address'           => 'nullable|string|max:500',
            'district_id'       => 'nullable|integer|exists:districts,id',
            'shipping_zone_id'  => 'nullable|integer|exists:shipping_zones,id',
        ]);

        try {
            $this->incompleteCheckouts->capture(
                $validated['idempotency_token'],
                $validated,
                session('cart', [])
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Incomplete checkout capture failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    /**
     * Display the checkout page.
     */
    public function index(): View|RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('storefront.cart.index')
                ->with('error', __('Your cart is empty.'));
        }

        $cartSubtotal = $this->storefrontService->calculateCartSubtotal($cart);
        $coupon       = session('coupon');
        $discount     = $coupon ? ($coupon['discount'] ?? 0) : 0;
        $cartItems    = $cart;

        // Districts dropdown — pulled from the canonical districts table.
        // The view renders option labels using $district->name to match the
        // checkout JS, so we alias district_name → name in the query.
        $districts = District::where('is_active', true)
            ->orderBy('district_name')
            ->get(['id', 'district_name as name']);

        // Shipping zones drive the "Delivery Charge" dropdown directly now —
        // customers pick a zone explicitly instead of relying on a district
        // lookup. We still ship the district_ids in case the JS or a future
        // UX wants to suggest a zone from the picked district.
        $shippingZones = ShippingZone::active()->with('districts:id')->orderBy('flat_rate')->get();
        $zonesData     = [];
        foreach ($shippingZones as $zone) {
            $zonesData[] = [
                'id'             => $zone->id,
                'name'           => $zone->name,
                'bn_name'        => $zone->bn_name,
                'label'          => $zone->name . ($zone->bn_name ? ' (' . $zone->bn_name . ')' : ''),
                'rate'           => (float) $zone->flat_rate,
                'threshold'      => (float) ($zone->free_shipping_threshold ?? 0),
                'estimated_days' => $zone->estimated_days,
                'district_ids'   => $zone->districts->pluck('id')->all(),
            ];
        }

        // No charge until the customer picks a district — quotes a charge
        // they then have to pay would be misleading.
        $shippingCharge = 0;
        $cartTotal      = round($cartSubtotal - $discount + $shippingCharge, 2);

        // Load saved customer info: prioritize logged-in customer over cookie
        $savedCustomer = $this->getSavedCustomerInfo();

        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $savedCustomer = [
                'name'        => $customer->name,
                'email'       => $customer->email ?? '',
                'phone'       => $customer->phone,
                'address'     => $customer->address ?? '',
                'district_id' => null,
            ];
        }

        // One-time token for idempotent order placement (see process()).
        // A fresh token per render; the unique table column dedupes submits.
        $idempotencyToken = (string) \Illuminate\Support\Str::uuid();

        return view('ecommerce::storefront.pages.checkout.index', compact(
            'cart',
            'cartSubtotal',
            'coupon',
            'discount',
            'shippingCharge',
            'cartTotal',
            'cartItems',
            'savedCustomer',
            'districts',
            'zonesData',
            'idempotencyToken'
        ) + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow')]);
    }

    /**
     * Process the checkout and create the order.
     */
    public function process(Request $request): RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('storefront.cart.index')
                ->with('error', __('Your cart is empty.'));
        }

        // Re-validate prices from database to prevent stale/tampered session prices
        $priceChanged = false;
        foreach ($cart as $key => &$item) {
            // Combo lines: re-validate against the DB combo (must still be
            // active) and recompute the effective price server-side so a
            // tampered/stale session price can never be trusted.
            if (($item['type'] ?? 'product') === 'combo') {
                $combo = \Modules\Ecommerce\Models\Combo::active()->with('items.product', 'items.variant')->find($item['combo_id'] ?? null);
                if (! $combo) {
                    unset($cart[$key]);
                    $priceChanged = true;
                    continue;
                }
                $comboPrice = app(\Modules\Ecommerce\Services\ComboService::class)->effectivePrice($combo);
                if (abs((float) $item['price'] - $comboPrice) > 0.01) {
                    $item['price'] = $comboPrice;
                    $priceChanged = true;
                }
                continue;
            }

            $product = \Modules\Product\Models\Product::storefrontVisible()->find($item['product_id']);
            if (!$product) {
                unset($cart[$key]);
                $priceChanged = true;
                continue;
            }

            $currentPrice = $this->storefrontService->calculateEffectivePrice($product);

            if ($item['variant_id'] ?? null) {
                $variant = \Modules\Variant\Models\ProductVariant::active()->find($item['variant_id']);

                // The variant must still be an ACTIVE variant of this product.
                // If it was deactivated/removed after being added to the cart,
                // drop it so an inactive variant can never be ordered.
                if (! $variant || $variant->product_id !== $product->id) {
                    unset($cart[$key]);
                    $priceChanged = true;
                    continue;
                }

                // Discount applies to the variant's own price — must match
                // the cart-add calculation so the guard doesn't reprice.
                $currentPrice = $this->storefrontService->calculateEffectivePrice($product, $variant);
            } elseif ($product->isVariable()) {
                // A variable product with no variant reference is invalid.
                unset($cart[$key]);
                $priceChanged = true;
                continue;
            }

            if (abs((float) $item['price'] - $currentPrice) > 0.01) {
                $item['price'] = $currentPrice;
                $priceChanged = true;
            }
        }
        unset($item);

        if ($priceChanged) {
            session(['cart' => $cart]);
        }

        if (empty($cart)) {
            return redirect()->route('storefront.cart.index')
                ->with('error', __('Some items are no longer available. Please review your cart.'));
        }

        // Stock availability — refuse the order only if the product is
        // actively tracked AND has at least one warehouse_stock row showing
        // depletion. A product with no stock rows at all is treated as
        // "untracked yet" (typical for a freshly-seeded catalog) so we
        // don't block legitimate orders before any inventory has moved.
        $inventory = app(\Modules\Inventory\Services\InventoryService::class);
        $stockShortages = [];
        foreach ($cart as $item) {
            // A combo line is checked against its derived availability (the min
            // over its components); ComboService applies the same stock rules.
            if (($item['type'] ?? 'product') === 'combo') {
                $combo = \Modules\Ecommerce\Models\Combo::with('items.product')->find($item['combo_id'] ?? null);
                $wanted = (int) ($item['quantity'] ?? 0);
                if ($combo && ! app(\Modules\Ecommerce\Services\ComboService::class)->isInStock($combo, $wanted)) {
                    $stockShortages[] = ($item['name'] ?? 'Combo') . ' — ' . __('not enough stock');
                }
                continue;
            }

            $product = \Modules\Product\Models\Product::find($item['product_id']);
            if (! $product || ! $product->track_stock) {
                continue;
            }
            // Products that permit overselling can always be ordered, even at or
            // below zero stock — mirrors the cart's is_in_stock allowance.
            if ($product->allow_negative_stock) {
                continue;
            }
            // Has stock ever been recorded for this product/variant? If
            // not, skip the check entirely.
            $hasStockHistory = \DB::table('warehouse_stock')
                ->where('product_id', (int) $item['product_id'])
                ->when($item['variant_id'] ?? null, fn ($q, $v) => $q->where('variant_id', $v))
                ->exists();
            if (! $hasStockHistory) {
                continue;
            }

            $available = $inventory->getStockLevel(
                (int) $item['product_id'],
                $item['variant_id'] ?? null,
            );
            $wanted = (int) ($item['quantity'] ?? 0);
            if ($wanted > $available) {
                $label = $item['name'] ?? ('Product #' . $item['product_id']);
                if (! empty($item['variant_name'])) {
                    $label .= ' (' . $item['variant_name'] . ')';
                }
                $stockShortages[] = $available > 0
                    ? "{$label} — only {$available} left, you asked for {$wanted}"
                    : "{$label} — out of stock";
            }
        }
        if (! empty($stockShortages)) {
            return redirect()->route('storefront.cart.index')
                ->with('error', __('Not enough stock: ') . implode('; ', $stockShortages));
        }

        $validated = $request->validate([
            'customer_name'     => 'required|string|max:255',
            'customer_email'    => 'nullable|email|max:255',
            'customer_phone'    => ['required', 'string', 'max:20', new \App\Rules\PhoneNumber],
            'alt_phone'         => ['nullable', 'string', 'max:20', new \App\Rules\PhoneNumber],
            'address'           => 'required|string|max:500',
            // District is now optional — the customer chooses a shipping
            // zone explicitly via the Delivery Charge dropdown, and the
            // district just enriches the address string.
            'district_id'       => 'nullable|integer|exists:districts,id',
            'shipping_zone_id'  => 'required|integer|exists:shipping_zones,id',
            'payment_method'    => 'required|in:cod',
            'notes'             => 'nullable|string|max:500',
        ]);

        $districtName = '';
        if (! empty($validated['district_id'])) {
            $district = District::find($validated['district_id']);
            $districtName = $district ? $district->district_name : '';
        }

        $shippingAddress = $districtName
            ? $validated['address'] . ', ' . $districtName
            : $validated['address'];

        // Always re-fetch the zone server-side (active only). Never trust a
        // rate the form could carry — the picked zone_id is the only
        // input we use to compute the charge.
        $shippingCharge = 0;
        $zone = ShippingZone::active()->find($validated['shipping_zone_id']);
        if ($zone) {
            $shippingCharge = (float) $zone->flat_rate;
            $subtotalCheck = $this->storefrontService->calculateCartSubtotal($cart);
            if ($zone->free_shipping_threshold && $subtotalCheck >= (float) $zone->free_shipping_threshold) {
                $shippingCharge = 0;
            }
        }

        $subtotal       = $this->storefrontService->calculateCartSubtotal($cart);
        $coupon         = session('coupon');
        $discountAmount = $coupon ? ($coupon['discount'] ?? 0) : 0;

        // Revalidate coupon before placing order
        if ($coupon) {
            $validCoupon = $this->storefrontService->findValidCoupon($coupon['code'], $subtotal);
            if (!$validCoupon) {
                session()->forget('coupon');
                return redirect()->route('storefront.checkout.index')
                    ->with('error', __('The applied coupon is no longer valid. Please review your order.'));
            }
            $discountAmount = $validCoupon->calculateDiscount($subtotal);
        }

        $orderData = [
            'customer_name'    => $validated['customer_name'],
            'customer_email'   => $validated['customer_email'] ?? null,
            'customer_phone'   => $validated['customer_phone'],
            'shipping_address' => $shippingAddress,
            'billing_address'  => $shippingAddress,
            'shipping_zone_id' => $validated['shipping_zone_id'],
            'payment_method'   => $validated['payment_method'],
            'coupon_code'      => $coupon['code'] ?? null,
            'notes'            => trim(implode(' | ', array_filter([
                !empty($validated['alt_phone']) ? 'Alt: ' . $validated['alt_phone'] : null,
                $validated['notes'] ?? null,
            ]))),
        ];

        // Associate order with logged-in customer
        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $orderData['customer_id'] = $customer->id;
        }

        // Idempotency guard — claim the one-time checkout token before creating
        // the order so a duplicate submit (double-click, raced POST, client
        // retry) cannot place a second order. insertOrIgnore commits at once,
        // so a concurrent request sees the claim immediately.
        $token = (string) $request->input('idempotency_token', '');
        if ($token !== '') {
            $claimed = \DB::table('checkout_idempotency_keys')->insertOrIgnore([
                'token'      => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (! $claimed) {
                // Replay of an already-claimed token. If the original order
                // finished, show its confirmation; otherwise it is still
                // in-flight — send the shopper back to the cart to wait.
                $existing = \DB::table('checkout_idempotency_keys')->where('token', $token)->first();
                if ($existing && $existing->order_number) {
                    return redirect()->route('storefront.checkout.success', $existing->order_number)
                        ->with('success', __('Your order has already been placed.'));
                }

                return redirect()->route('storefront.cart.index')
                    ->with('error', __('Your order is already being processed. Please wait a moment.'));
            }
        }

        try {
            $order = $this->storefrontService->createOrder(
                $orderData,
                $cart,
                $subtotal,
                $discountAmount,
                $shippingCharge
            );
        } catch (\Throwable $e) {
            // Release the claim so the shopper can retry after a failure.
            if ($token !== '') {
                \DB::table('checkout_idempotency_keys')
                    ->where('token', $token)
                    ->whereNull('order_number')
                    ->delete();
            }
            throw $e;
        }

        // Bind the order to the claimed token so any later replay of this
        // token lands on the same confirmation page.
        if ($token !== '') {
            \DB::table('checkout_idempotency_keys')
                ->where('token', $token)
                ->update([
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'updated_at'   => now(),
                ]);
        }

        // The shopper completed checkout — convert any abandoned-checkout
        // capture (matched by token, or phone if the page was re-rendered
        // with a fresh token) so its Incompleted placeholder disappears and
        // only the real pending sale remains. Best-effort: never blocks.
        try {
            $this->incompleteCheckouts->convert($token, $validated['customer_phone'], $order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Incomplete checkout conversion failed for order {$order->order_number}: {$e->getMessage()}");
        }

        // Fraud check calls an EXTERNAL API. Run it AFTER the response is sent
        // (deferred) so a slow third-party call never delays the order
        // confirmation redirect. A held response makes impatient shoppers
        // re-submit, which trips the idempotency in-flight guard and bounces
        // them to the cart — the intermittent "Place Order → cart" report
        // (Bug_90). The result is only used for admin review, never blocks.
        $fraudPhone = $validated['customer_phone'];
        $fraudService = $this->fraudCheckService;
        defer(function () use ($order, $fraudPhone, $fraudService) {
            try {
                $fraudReport = $fraudService->check($fraudPhone);
                if ($fraudReport) {
                    $order->update([
                        'fraud_report' => $fraudReport,
                        'fraud_score'  => $fraudReport['aggregate']['success_ratio'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('Fraud check error on order ' . $order->order_number, ['error' => $e->getMessage()]);
            }
        });

        // Capture ad-click identifiers and send the server-side (CAPI) Purchase
        // after the response. Registered AFTER the fraud-check defer above, so the
        // deferred callbacks run in order and the fraud gate sees fraud_report.
        // The browser Purchase fires on the success page; both share an event_id
        // so Meta dedups them. Best-effort only — never blocks the order.
        app(\Modules\Ecommerce\Services\TrackingService::class)
            ->reportPurchaseAtPlacement($order, $request);

        // Save customer info in cookie for auto-populate on next visit (90 days)
        $customerCookie = encrypt(json_encode([
            'name'        => $validated['customer_name'],
            'email'       => $validated['customer_email'] ?? '',
            'phone'       => $validated['customer_phone'],
            'address'     => $validated['address'],
            'district_id' => $validated['district_id'] ?? null,
        ]));
        Cookie::queue('storefront_customer', $customerCookie, 60 * 24 * 90);

        // Clear cart and coupon after successful order
        session()->forget(['cart', 'coupon']);

        return redirect()->route('storefront.checkout.success', $order->order_number)
            ->with('success', __('Order placed successfully!'))
            ->with('purchase_order', $order->order_number);
    }

    /**
     * Display the order success/confirmation page.
     */
    public function success(string $orderNumber): View
    {
        $order = $this->storefrontService->findOrderByNumber($orderNumber);

        if (!$order) {
            abort(404);
        }

        // Browser-side Purchase fires only on the first arrival from checkout
        // (one-time session flash) — reloads, revisits, and shared links never
        // re-fire it. Fraud-gated inside browserPurchasePayload (null = no script).
        $purchasePayload = null;
        if (session('purchase_order') === $orderNumber) {
            $purchasePayload = app(\Modules\Ecommerce\Services\TrackingService::class)
                ->browserPurchasePayload($order);
        }

        return view(
            'ecommerce::storefront.pages.checkout.success',
            compact('order', 'purchasePayload') + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow')]
        );
    }

    /**
     * Display the order cancelled page.
     */
    public function cancel(): View
    {
        return view('ecommerce::storefront.pages.checkout.cancel', ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow')]);
    }

    /**
     * Retrieve saved customer info from cookie.
     */
    private function getSavedCustomerInfo(): ?array
    {
        $cookie = Cookie::get('storefront_customer');

        if (!$cookie) {
            return null;
        }

        try {
            return json_decode(decrypt($cookie), true);
        } catch (\Exception $e) {
            return null;
        }
    }
}
