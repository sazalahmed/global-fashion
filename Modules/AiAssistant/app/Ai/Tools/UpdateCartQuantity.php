<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Ecommerce\Services\StorefrontService;
use Stringable;

/**
 * Change the quantity of a cart item, or remove it.
 *   quantity = 0   → remove the item entirely
 *   quantity > 0   → set to that exact quantity (not add to existing)
 *
 * Identifies the line by product_id (+ optional variant_id) so the
 * agent doesn't need to remember internal cart_keys.
 */
class UpdateCartQuantity implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected StorefrontService $storefront) {}

    public function description(): Stringable|string
    {
        return 'Change quantity of an existing cart item, or remove it. '
            . 'Set quantity=0 to remove. Use for "change milk to 3", "remove the shirt", '
            . '"only 1 instead of 2" requests.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'update_cart_quantity');

        $productId = (int) ($request['product_id'] ?? 0);
        $quantity = (int) ($request['quantity'] ?? -1);
        $variantId = $request['variant_id'] ?? null;

        if ($productId <= 0) {
            return json_encode(['success' => false, 'message' => 'product_id is required.']);
        }

        if ($quantity < 0 || $quantity > 999) {
            return json_encode([
                'success' => false,
                'message' => 'Quantity must be between 0 and 999 (0 removes the item).',
            ]);
        }

        $cartKey = $variantId ? $productId . '-' . $variantId : (string) $productId;
        $cart = session('cart', []);

        if (! isset($cart[$cartKey])) {
            return json_encode([
                'success' => false,
                'message' => 'That product is not in the cart. Did the customer mean to add it via add_to_cart?',
            ]);
        }

        $itemName = $cart[$cartKey]['name'] ?? 'Item';

        if ($quantity === 0) {
            unset($cart[$cartKey]);
            $action = sprintf('Removed %s from cart.', $itemName);
        } else {
            $cart[$cartKey]['quantity'] = $quantity;
            $action = sprintf('Updated %s to %d.', $itemName, $quantity);
        }

        session(['cart' => $cart]);

        $subtotal = $this->storefront->calculateCartSubtotal($cart);

        return json_encode([
            'success' => true,
            'message' => $action,
            'cart_count' => array_sum(array_column($cart, 'quantity')),
            'cart_subtotal' => currency_symbol() . ' ' . number_format($subtotal, 2),
            'cart_subtotal_raw' => $subtotal,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('Product ID of the cart line to change. Find it via view_cart if unsure.')
                ->required(),
            'quantity' => $schema->integer()
                ->description('New quantity. Use 0 to remove the item. Range 0–999.')
                ->required(),
            'variant_id' => $schema->integer()
                ->description('Optional variant ID if the cart line is for a specific variant.'),
        ];
    }
}
