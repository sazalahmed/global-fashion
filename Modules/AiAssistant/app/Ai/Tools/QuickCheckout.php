<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Customer\Models\Area;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Product\Models\Product;
use Modules\Variant\Models\ProductVariant;
use Stringable;

// Product/ProductVariant are used in revalidateCart()

class QuickCheckout implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected StorefrontService $storefront) {}

    public function description(): Stringable|string
    {
        return 'Place a Cash-on-Delivery order for the session cart. '
            . 'Call add_to_cart for each item first. Requires name, BD phone, district, address.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'quick_checkout');

        $phone = trim((string) ($request['customer_phone'] ?? ''));
        if (! preg_match('/^01[3-9]\d{8}$/', $phone)) {
            return json_encode([
                'success' => false,
                'message' => 'Invalid Bangladesh phone number. It must be 11 digits starting with 013-019 (e.g. 01712345678).',
            ]);
        }

        $name = trim((string) ($request['customer_name'] ?? ''));
        $address = trim((string) ($request['address'] ?? ''));
        $districtRaw = trim((string) ($request['district'] ?? ''));
        $paymentMethod = strtolower(trim((string) ($request['payment_method'] ?? 'cod')));

        foreach (['customer_name' => $name, 'customer_phone' => $phone, 'district' => $districtRaw, 'address' => $address] as $field => $value) {
            if ($value === '') {
                return json_encode(['success' => false, 'message' => "Missing required field: {$field}."]);
            }
        }

        if (! in_array($paymentMethod, ['cod', 'cash', 'cash on delivery'], true)) {
            $paymentMethod = 'cod';
        } else {
            $paymentMethod = 'cod';
        }

        // Reject any AI-supplied money field — checkout totals are derived
        // server-side from the session cart and ShippingZone configuration.
        foreach (['grand_total', 'subtotal', 'shipping', 'shipping_charge', 'discount', 'price'] as $banned) {
            if (isset($request[$banned])) {
                \Log::warning('AI QuickCheckout attempted to set financial field', [
                    'field' => $banned,
                    'value' => $request[$banned],
                ]);
            }
        }

        $cart = session('cart', []);
        if (empty($cart)) {
            // Surface a clear recovery hint to the agent. If we have a
            // focused product the customer was looking at, tell the agent
            // to add_to_cart with that ID and retry — instead of saying
            // "cart is empty" to the customer, which sounds wrong when
            // they just said "order this".
            $hint = 'Cart is empty. Call add_to_cart first, then retry quick_checkout.';
            if ($focusId = session('ai_last_shown_product_id')) {
                $focusName = session('ai_last_shown_product_name', '');
                $hint .= " The customer is currently focused on product_id={$focusId} ({$focusName}). "
                    . "If they said 'order this / aita order koro / order it', call "
                    . "add_to_cart(product_id={$focusId}, quantity=1) NOW and then retry quick_checkout.";
            }
            return json_encode([
                'success' => false,
                'message' => $hint,
            ]);
        }

        // Per-customer rate limit: at most 3 successful checkouts per minute
        // per IP. Stops a runaway loop from spamming orders.
        $rateKey = 'ai_quick_checkout:' . $this->rateLimiterKey();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateKey, 3)) {
            return json_encode([
                'success' => false,
                'message' => 'Too many checkout attempts. Please wait a minute before retrying.',
            ]);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateKey, 60);

        $district = is_numeric($districtRaw)
            ? Area::query()->where('level', 'district')->find((int) $districtRaw)
            : Area::query()->where('level', 'district')->whereRaw('LOWER(name) = ?', [mb_strtolower($districtRaw)])->first();

        if (! $district) {
            $available = Area::query()->where('level', 'district')->orderBy('name')->limit(15)->pluck('name')->implode(', ');
            return json_encode([
                'success' => false,
                'message' => "District '{$districtRaw}' not found. Ask the user to choose one of: {$available}, ...",
            ]);
        }

        [$cart, $error] = $this->revalidateCart($cart);
        if ($error) {
            return json_encode(['success' => false, 'message' => $error]);
        }

        $subtotal = $this->storefront->calculateCartSubtotal($cart);

        // Re-validate any session coupon against the CURRENT subtotal —
        // an item could have been removed since the customer applied it.
        $discountAmount = 0.0;
        $couponCode = null;
        if ($couponSession = session('coupon')) {
            $coupon = $this->storefront->findValidCoupon($couponSession['code'] ?? '', $subtotal);
            if ($coupon) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
                $couponCode = $coupon->code;
            }
        }

        [$shippingCharge, $shippingZone] = $this->resolveShipping($district->id, $subtotal - $discountAmount);
        $shippingAddress = $address . ', ' . $district->name;

        $orderData = [
            'customer_name' => $name,
            'customer_email' => $request['customer_email'] ?? null,
            'customer_phone' => $phone,
            'shipping_address' => $shippingAddress,
            'billing_address' => $shippingAddress,
            'payment_method' => $paymentMethod,
            'coupon_code' => $couponCode,
            'notes' => 'Placed via AI chat assistant.',
        ];

        if ($customer = Auth::guard('customer')->user()) {
            $orderData['customer_id'] = $customer->id;
        }

        try {
            $order = $this->storefront->createOrder(
                $orderData,
                $cart,
                $subtotal,
                discountAmount: $discountAmount,
                shippingCharge: $shippingCharge,
            );
        } catch (\Throwable $e) {
            report($e);
            return json_encode([
                'success' => false,
                'message' => 'Order placement failed. Please ask the user to try again or contact support.',
            ]);
        }

        session()->forget(['cart', 'coupon']);

        // Server-side (CAPI) Purchase at placement — same rationale as the chat
        // controller path: no success-page flash, CAPI is the only Purchase signal.
        app(\Modules\Ecommerce\Services\TrackingService::class)
            ->reportPurchaseAtPlacement($order, request());

        $eta = $shippingZone?->estimated_days;
        $freeShippingNotice = ($shippingZone && $shippingZone->free_shipping_threshold && $shippingCharge == 0)
            ? sprintf('Free delivery applied (order over ' . currency_symbol() . ' %s).', number_format($shippingZone->free_shipping_threshold, 0))
            : null;

        return json_encode([
            'success' => true,
            'order_number' => $order->order_number,
            'grand_total' => currency_symbol() . ' ' . number_format($order->grand_total, 2),
            'grand_total_raw' => (float) $order->grand_total,
            'subtotal' => currency_symbol() . ' ' . number_format($subtotal, 2),
            'discount' => $discountAmount > 0 ? currency_symbol() . ' ' . number_format($discountAmount, 2) : null,
            'coupon_code' => $couponCode,
            'shipping' => $shippingCharge > 0 ? currency_symbol() . ' ' . number_format($shippingCharge, 2) : 'Free',
            'payment_method' => 'Cash on Delivery',
            'payment_instructions' => sprintf(
                'Order #%s confirmed. Pay ' . currency_symbol() . ' %s in cash to the delivery agent when the order arrives at %s.',
                $order->order_number,
                number_format($order->grand_total, 2),
                $shippingAddress
            ),
            'estimated_delivery_days' => $eta,
            'shipping_zone' => $shippingZone?->name,
            'free_shipping_notice' => $freeShippingNotice,
            'success_url' => route('storefront.checkout.success', $order->order_number),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_name' => $schema->string()->description('Full name of the customer.')->required(),
            'customer_phone' => $schema->string()->description('BD phone number, 11 digits starting with 01[3-9].')->required(),
            'customer_email' => $schema->string()->description('Optional email address.'),
            'district' => $schema->string()->description('District name (e.g. "Dhaka") or numeric district ID.')->required(),
            'address' => $schema->string()->description('Full street address including house, road, area.')->required(),
            'payment_method' => $schema->string()->description('Payment method. Currently only "cod" (Cash on Delivery) is supported.')->required(),
        ];
    }

    /**
     * Re-fetch DB prices for every item in the existing cart, rejecting any
     * AI-supplied price drift. Items whose product was deactivated are removed.
     *
     * @return array{0: array<string, array<string, mixed>>, 1: ?string}
     */
    protected function revalidateCart(array $cart): array
    {
        foreach ($cart as $key => $item) {
            $product = Product::query()->where('status', 'active')->find($item['product_id'] ?? 0);
            if (! $product) {
                unset($cart[$key]);
                continue;
            }

            $price = $this->storefront->calculateEffectivePrice($product);

            if (! empty($item['variant_id'])) {
                $variant = ProductVariant::query()->find($item['variant_id']);
                if ($variant && $variant->product_id === $product->id && $variant->sell_price) {
                    $price = (float) $variant->sell_price;
                }
            }

            $cart[$key]['price'] = $price;
        }

        if (empty($cart)) {
            return [[], 'All cart items are no longer available. Ask the user to add products again.'];
        }

        return [$cart, null];
    }

    protected function rateLimiterKey(): string
    {
        $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();
        return $customer
            ? 'customer:' . $customer->id
            : 'ip:' . (request()->ip() ?: 'unknown');
    }

    /**
     * Resolve the matching ShippingZone for a district and return both the
     * charge and the zone (so callers can surface ETA + free-shipping notice).
     *
     * @return array{0: float, 1: ?\Modules\Ecommerce\Models\ShippingZone}
     */
    protected function resolveShipping(int $districtId, float $subtotal): array
    {
        foreach (ShippingZone::active()->get() as $zone) {
            if ($zone->containsDistrict($districtId)) {
                $rate = (float) $zone->flat_rate;
                if ($zone->free_shipping_threshold && $subtotal >= (float) $zone->free_shipping_threshold) {
                    return [0.0, $zone];
                }
                return [$rate, $zone];
            }
        }

        return [0.0, null];
    }
}
