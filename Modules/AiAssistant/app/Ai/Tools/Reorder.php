<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Product\Models\Product;
use Modules\Variant\Models\ProductVariant;
use Stringable;

/**
 * Pull the items from a past order into the session cart. Customer can
 * then call quick_checkout to place the same order again. Skips items
 * whose product is no longer active (out of catalog).
 *
 * Ownership: order must belong to the authenticated customer.
 */
class Reorder implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected StorefrontService $storefront) {}

    public function description(): Stringable|string
    {
        return 'Copy items from a previous order into the cart so the customer can buy '
            . 'the same things again. Only works for logged-in customers and only for '
            . 'their own orders. Skips items no longer in the catalog.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'reorder');

        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return json_encode([
                'success' => false,
                'message' => 'Reorder is only available for logged-in customers.',
            ]);
        }

        $orderNumber = trim((string) ($request['order_number'] ?? ''));
        if ($orderNumber === '') {
            return json_encode(['success' => false, 'message' => 'order_number is required.']);
        }

        $order = EcommerceOrder::query()
            ->with('items')
            ->where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $order) {
            return json_encode([
                'success' => false,
                'message' => "Order {$orderNumber} not found in your account.",
            ]);
        }

        $cart = session('cart', []);
        $added = 0;
        $skipped = [];

        foreach ($order->items as $item) {
            $product = Product::query()->where('status', 'active')->find($item->product_id);
            if (! $product) {
                $skipped[] = $item->product_name ?? "Product #{$item->product_id}";
                continue;
            }

            $variantId = $item->variant_id ?? null;
            $price = $this->storefront->calculateEffectivePrice($product);
            $variantName = null;
            if ($variantId) {
                $variant = ProductVariant::query()->find($variantId);
                if ($variant && $variant->product_id === $product->id && $variant->sell_price) {
                    $price = (float) $variant->sell_price;
                    $variantName = $variant->variant_name;
                } else {
                    $variantId = null;
                }
            }

            $key = $variantId ? $product->id . '-' . $variantId : (string) $product->id;
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $price,
                'sell_price' => (float) $product->sell_price,
                'image' => $product->image,
                'quantity' => max(1, (int) $item->quantity),
                'sku' => $product->sku,
            ];
            $added++;
        }

        session(['cart' => $cart]);
        $subtotal = $this->storefront->calculateCartSubtotal($cart);

        return json_encode([
            'success' => true,
            'message' => sprintf(
                'Added %d item(s) from order %s to your cart.%s',
                $added,
                $order->order_number,
                $skipped ? ' Skipped (no longer available): ' . implode(', ', $skipped) : ''
            ),
            'added_count' => $added,
            'skipped' => $skipped,
            'cart_subtotal' => currency_symbol() . ' ' . number_format($subtotal, 2),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()
                ->description('The order number to reorder (e.g. "ECO-2026-00042"). Must belong to the logged-in customer.')
                ->required(),
        ];
    }
}
