<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Product\Models\Product;
use Stringable;

/**
 * Fetches rich details for one product by ID. Used when the customer
 * asks a follow-up question about a previously surfaced product:
 *   "aita somporke bistarito bolo"  → tell me details
 *   "ei product er warranty ki?"   → what's the warranty
 *   "specs ki?"                    → what are the specs
 *
 * Returns a JSON summary — NOT a PRODUCT_CARDS block. The agent
 * paraphrases the relevant fields into a conversational answer.
 *
 * Cost/supplier fields are never returned — same PII rules as
 * search_products.
 */
class GetProductDetails implements Tool
{
    use LogsToolInvocation;

    public function description(): Stringable|string
    {
        return 'Get full details (description, brand, category, warranty, weight) '
            . 'for ONE product by id. Use when the customer asks "tell me about this", '
            . '"specs?", "warranty?", or "aita somporke bolo" about a product you '
            . 'just surfaced.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'get_product_details');

        $productId = (int) ($request['product_id'] ?? 0);
        if ($productId <= 0) {
            return json_encode([
                'success' => false,
                'message' => 'product_id is required.',
            ]);
        }

        $product = Product::query()
            ->with(['brand', 'category', 'unit', 'images'])
            ->where('status', 'active')
            ->find($productId);

        if (! $product) {
            return json_encode([
                'success' => false,
                'message' => 'Product not found or inactive.',
            ]);
        }

        // Track this as the most recently surfaced product so the system
        // prompt can keep the next turn anchored on the right item.
        session([
            'ai_last_shown_product_id' => $product->id,
            'ai_last_shown_product_name' => $product->name,
        ]);

        // Build image gallery — primary first, then by sort_order.
        $images = $product->images
            ->sortBy([['is_primary', 'desc'], ['sort_order', 'asc']])
            ->map(fn ($img) => [
                'src' => url('storage/' . $img->image_path),
                'alt' => $img->alt_text ?: $product->name,
            ])
            ->values()
            ->all();

        // Fallback: thumbnail field if no ProductImage rows exist.
        if (empty($images) && $product->thumbnail) {
            $images[] = ['src' => url('storage/' . $product->thumbnail), 'alt' => $product->name];
        }

        // Customer-safe projection. Never expose cost_price, supplier_id,
        // valuation_method, internal fields.
        $payload = [
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'price' => currency_symbol() . ' ' . number_format((float) $product->sell_price, 2),
                'description' => strip_tags((string) $product->description),
                'long_description' => strip_tags((string) $product->long_description),
                'warranty' => $product->warranty,
                'weight' => $product->weight ? $product->weight . ' kg' : null,
                'country_of_origin' => $product->country_of_origin,
                'unit' => $product->unit?->name,
                'image_count' => count($images),
                'url' => route('storefront.shop.show', ['slug' => $product->slug]),
            ],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Tell the frontend "this is now the focused product" so the JS
        // productFocusStack gets pushed. Without this marker, a customer
        // who asks "Men's Polo Shirt details" right after a SearchProducts
        // hit on a totally different product would later "ami aita kinte
        // cai" → buy the WRONG product (the search hit, not the one they
        // just asked about). The frontend strips this marker from display.
        $focus = ['id' => $product->id, 'name' => $product->name];
        $json .= "\n<<PRODUCT_FOCUS:" . json_encode($focus, JSON_UNESCAPED_UNICODE) . '>>';

        // Append the IMAGES block ONLY when there's something to show. The
        // frontend regex-extracts it and renders an inline gallery; the
        // model is told (in the prompt) not to reproduce the block.
        if (! empty($images)) {
            $json .= "\n<<PRODUCT_IMAGES:" . json_encode($images, JSON_UNESCAPED_UNICODE) . '>>';
        }

        return $json;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The product ID, usually the id you saw on a card from a recent search_products call.')
                ->required(),
        ];
    }
}
