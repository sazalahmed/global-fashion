<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Stringable;

/**
 * Empty the entire session cart. Used when the customer says "clear cart",
 * "delete all", "shob remove koro", or wants to start fresh.
 *
 * Confirm verbally before calling — clearing is destructive.
 */
class ClearCart implements Tool
{
    use LogsToolInvocation;

    public function description(): Stringable|string
    {
        return 'Empty the entire cart. Destructive — confirm with the customer first '
            . '("are you sure you want to clear the cart?") before calling. Also clears any applied coupon.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'clear_cart');

        $cart = session('cart', []);
        $itemCount = array_sum(array_column($cart, 'quantity'));

        session()->forget(['cart', 'coupon']);

        return json_encode([
            'success' => true,
            'message' => $itemCount > 0
                ? "Cleared {$itemCount} item(s) from the cart."
                : 'Cart was already empty.',
            'cart_count' => 0,
            'cart_subtotal' => currency_symbol() . ' 0.00',
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
