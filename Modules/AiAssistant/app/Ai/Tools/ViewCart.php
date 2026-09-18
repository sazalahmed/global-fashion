<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Ecommerce\Services\StorefrontService;
use Stringable;

/**
 * Read the customer's current session cart. Agent uses this to:
 *  - answer "what's in my cart?" / "amar cart e ki ache?"
 *  - find the right product_id before update_cart_quantity / clear_cart
 *  - confirm contents before quick_checkout
 *
 * Cart items always show the DB price, not the price stored in session,
 * so any stale value can't mislead the agent or the customer.
 */
class ViewCart implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected StorefrontService $storefront) {}

    public function description(): Stringable|string
    {
        return 'Show what is currently in the customer\'s cart. '
            . 'Returns each item\'s product_id, name, quantity, unit price, and line total, '
            . 'plus the cart subtotal. Empty cart returns an empty list.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'view_cart');

        $cart = session('cart', []);

        if (empty($cart)) {
            return json_encode([
                'count' => 0,
                'subtotal' => currency_symbol() . ' 0.00',
                'items' => [],
                'message' => 'Cart is empty.',
            ]);
        }

        $items = [];
        foreach ($cart as $key => $row) {
            $items[] = [
                'cart_key' => (string) $key,
                'product_id' => (int) ($row['product_id'] ?? 0),
                'variant_id' => $row['variant_id'] ?? null,
                'variant_name' => $row['variant_name'] ?? null,
                'name' => (string) ($row['name'] ?? 'Unknown'),
                'quantity' => (int) ($row['quantity'] ?? 0),
                'unit_price' => currency_symbol() . ' ' . number_format((float) ($row['price'] ?? 0), 2),
                'line_total' => currency_symbol() . ' ' . number_format(((float) ($row['price'] ?? 0)) * ((int) ($row['quantity'] ?? 0)), 2),
            ];
        }

        $subtotal = $this->storefront->calculateCartSubtotal($cart);

        // Re-validate any session coupon against the current subtotal so
        // we report the live discount, not the value frozen at apply time.
        $appliedCoupon = null;
        $discount = 0.0;
        if ($couponSession = session('coupon')) {
            $coupon = $this->storefront->findValidCoupon($couponSession['code'] ?? '', $subtotal);
            if ($coupon) {
                $discount = $coupon->calculateDiscount($subtotal);
                $appliedCoupon = [
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'discount' => currency_symbol() . ' ' . number_format($discount, 2),
                ];
            }
        }

        return json_encode([
            'count' => array_sum(array_column($items, 'quantity')),
            'subtotal' => currency_symbol() . ' ' . number_format($subtotal, 2),
            'discount' => $discount > 0 ? currency_symbol() . ' ' . number_format($discount, 2) : null,
            'total' => currency_symbol() . ' ' . number_format($subtotal - $discount, 2),
            'applied_coupon' => $appliedCoupon,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
