<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Product\Models\Product;
use Modules\Variant\Models\ProductVariant;
use Stringable;

class AddToCart implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected StorefrontService $storefront) {}

    public function description(): Stringable|string
    {
        return 'Add a product to the cart. Price comes from DB; do not pass any price field.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'add_to_cart');

        $productId = (int) ($request['product_id'] ?? 0);
        if ($productId <= 0) {
            return json_encode(['success' => false, 'message' => 'product_id is required.']);
        }
        $quantityRaw = (int) ($request['quantity'] ?? 1);
        $variantId = $request['variant_id'] ?? null;

        // Guard against obvious abuse: negative qty, zero, or oversize requests.
        if ($quantityRaw < 1 || $quantityRaw > 999) {
            \Log::warning('AI AddToCart suspicious quantity', [
                'product_id' => $productId,
                'quantity' => $quantityRaw,
            ]);
            return json_encode([
                'success' => false,
                'message' => 'Invalid quantity. Must be between 1 and 999.',
            ]);
        }
        $quantity = $quantityRaw;

        // Reject any AI-supplied price field outright (defense in depth — the
        // schema doesn't declare price either, but tools sometimes get unknown
        // keys in production).
        foreach (['price', 'unit_price', 'sell_price', 'cost_price', 'discount'] as $banned) {
            if (isset($request[$banned])) {
                \Log::warning('AI AddToCart attempted to set price field', [
                    'product_id' => $productId,
                    'field' => $banned,
                    'value' => $request[$banned],
                ]);
            }
        }

        $product = Product::query()->where('status', 'active')->find($productId);
        if (! $product) {
            return json_encode([
                'success' => false,
                'message' => 'Product not found or no longer available.',
            ]);
        }

        $price = $this->storefront->calculateEffectivePrice($product);
        $variantName = null;

        if ($variantId) {
            $variant = ProductVariant::with('attributeValues.attribute')->active()->find($variantId);
            if ($variant && $variant->product_id === $product->id) {
                if ($variant->sell_price) {
                    $price = (float) $variant->sell_price;
                }
                $variantName = $variant->variant_name;
            } else {
                $variantId = null;
            }
        }

        $cartKey = $variantId ? $product->id . '-' . $variantId : (string) $product->id;
        $cart = session('cart', []);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $price,
                'sell_price' => (float) $product->sell_price,
                'image' => $product->image,
                'quantity' => $quantity,
                'sku' => $product->sku,
            ];
        }

        session(['cart' => $cart]);

        $subtotal = $this->storefront->calculateCartSubtotal($cart);
        $count = array_sum(array_column($cart, 'quantity'));

        $payload = json_encode([
            'success' => true,
            'message' => sprintf('Added %dx %s to cart.', $quantity, $product->name),
            'cart_count' => $count,
            'cart_subtotal' => currency_symbol() . ' ' . number_format($subtotal, 2),
            'cart_subtotal_raw' => $subtotal,
        ], JSON_UNESCAPED_UNICODE);

        // Anchor the frontend focus stack on this product. The deterministic
        // place-order endpoint reads stack[0] when the customer submits the
        // delivery form, so the cart and the focus must agree.
        $payload .= "\n<<PRODUCT_FOCUS:" . json_encode([
            'id' => $product->id,
            'name' => $product->name,
        ], JSON_UNESCAPED_UNICODE) . '>>';

        return $payload;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The product ID returned by search_products.')
                ->required(),
            'quantity' => $schema->integer()
                ->description('Quantity to add. Default 1.'),
            'variant_id' => $schema->integer()
                ->description('Optional: variant ID if the product has size/color variants.'),
        ];
    }
}
