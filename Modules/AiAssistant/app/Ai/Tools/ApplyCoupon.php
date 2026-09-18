<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Ecommerce\Services\StorefrontService;
use Stringable;

/**
 * Apply a coupon code to the session cart. Wraps the same validation
 * the storefront CartController uses, so any coupon that works on the
 * regular cart page works in chat — including expiry checks, minimum-
 * order rules, and per-user limits enforced by Coupon::scopeValid().
 */
class ApplyCoupon implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected StorefrontService $storefront) {}

    public function description(): Stringable|string
    {
        return 'Apply a discount/promo coupon code to the session cart. '
            . 'Returns the discount amount if valid, or a reason if not. '
            . 'Cart must already have items.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'apply_coupon');

        $code = trim((string) ($request['code'] ?? ''));
        if ($code === '') {
            return json_encode(['success' => false, 'message' => 'Coupon code is required.']);
        }

        $cart = session('cart', []);
        $subtotal = $this->storefront->calculateCartSubtotal($cart);

        if ($subtotal <= 0) {
            return json_encode([
                'success' => false,
                'message' => 'Cart is empty. Add items before applying a coupon.',
            ]);
        }

        $coupon = $this->storefront->findValidCoupon($code, $subtotal);
        if (! $coupon) {
            return json_encode([
                'success' => false,
                'message' => "Coupon '{$code}' is invalid, expired, or doesn't meet the minimum order amount.",
            ]);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        session(['coupon' => [
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'discount' => $discount,
        ]]);

        return json_encode([
            'success' => true,
            'message' => sprintf('Coupon "%s" applied. Discount: ' . currency_symbol() . ' %s.', $coupon->code, number_format($discount, 2)),
            'code' => $coupon->code,
            'name' => $coupon->name,
            'discount' => currency_symbol() . ' ' . number_format($discount, 2),
            'discount_raw' => $discount,
            'new_total' => currency_symbol() . ' ' . number_format($subtotal - $discount, 2),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()
                ->description('The coupon code the customer wants to apply, e.g. "SAVE10", "BD500", "EID2026".')
                ->required(),
        ];
    }
}
