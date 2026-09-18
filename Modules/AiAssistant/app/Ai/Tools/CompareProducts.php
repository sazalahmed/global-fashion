<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Product\Models\Product;
use Stringable;

/**
 * Fetch details for 2–3 products side-by-side so the agent can give
 * the customer a structured comparison. Returns the same projection
 * as get_product_details for each item.
 */
class CompareProducts implements Tool
{
    use LogsToolInvocation;

    public function description(): Stringable|string
    {
        return 'Compare 2 or 3 products side by side. Returns name, brand, category, price, '
            . 'description, warranty, weight, country for each — agent paraphrases the '
            . 'differences into a recommendation.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'compare_products');

        $ids = $request['product_ids'] ?? null;
        if (is_string($ids)) {
            $ids = array_map('trim', explode(',', $ids));
        }
        $ids = array_values(array_filter(array_map('intval', (array) $ids), fn ($id) => $id > 0));

        if (count($ids) < 2) {
            return json_encode([
                'success' => false,
                'message' => 'Need at least 2 product_ids to compare.',
            ]);
        }
        if (count($ids) > 3) {
            $ids = array_slice($ids, 0, 3);
        }

        $products = Product::query()
            ->with(['brand', 'category'])
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->get();

        if ($products->count() < 2) {
            return json_encode([
                'success' => false,
                'message' => 'Could not find at least 2 of the requested products in the active catalog.',
            ]);
        }

        return json_encode([
            'success' => true,
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand?->name,
                'category' => $p->category?->name,
                'price' => currency_symbol() . ' ' . number_format((float) $p->sell_price, 2),
                'price_raw' => (float) $p->sell_price,
                'description' => strip_tags((string) $p->description),
                'warranty' => $p->warranty,
                'weight' => $p->weight ? $p->weight . ' kg' : null,
                'country_of_origin' => $p->country_of_origin,
                'url' => route('storefront.shop.show', ['slug' => $p->slug]),
            ])->all(),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_ids' => $schema->string()
                ->description('Comma-separated product IDs to compare, e.g. "12,14". 2 or 3 IDs.')
                ->required(),
        ];
    }
}
