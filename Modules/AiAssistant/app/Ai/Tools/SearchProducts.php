<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\AiAssistant\Services\ProductSearchService;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Stringable;

class SearchProducts implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected ProductSearchService $search) {}

    public function description(): Stringable|string
    {
        return 'Search the store catalog. For any product question. '
            . 'Set browse=true for "what do you have?" queries.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'search_products');

        $query = trim((string) ($request['query'] ?? ''));
        $browse = (bool) ($request['browse'] ?? false);

        if (! $browse && $query === '') {
            return json_encode(['error' => 'Query cannot be empty unless browse=true.']);
        }

        // Resolve category by NAME if the agent gave one — much more reliable
        // than the agent guessing numeric ids. category_id still works for
        // callers that already know the id (e.g. list_categories return).
        $categoryId = $request['category_id'] ?? null;
        if (! $categoryId && ! empty($request['category'])) {
            $catName = trim((string) $request['category']);
            $cat = Category::query()
                ->where('name', 'like', '%' . $catName . '%')
                ->orderByRaw('CASE WHEN LOWER(name) = ? THEN 0 ELSE 1 END', [mb_strtolower($catName)])
                ->first();
            if ($cat) {
                $categoryId = $cat->id;
            }
        }

        $filters = array_filter([
            'category_id' => $categoryId,
            'brand_id' => $request['brand_id'] ?? null,
            'min_price' => $request['min_price'] ?? null,
            'max_price' => $request['max_price'] ?? null,
            'ecom_only' => true,
        ], fn ($v) => $v !== null);

        // Browse mode (or query-less request) — surface the latest visible
        // products so the agent can answer "what do you have?" type questions.
        $usedFallback = false;

        if ($browse || $query === '') {
            $results = $this->browseProducts($filters);
        } else {
            $results = $this->search->search($query, $filters, limit: 10);
            if ($results->isEmpty()) {
                $results = $this->browseProducts($filters);
                $usedFallback = true;
            }
        }

        if ($results->isEmpty()) {
            return json_encode([
                'count' => 0,
                'message' => 'No products available.',
            ]);
        }

        // Trim each card to fields the UI actually uses. Drops sku, slug,
        // price_raw, in_stock — saves ~40% of payload size, which directly
        // cuts the tool-output tokens echoed back to the model on its next
        // reasoning step.
        $cards = $results->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'price' => currency_symbol() . ' ' . $this->formatBdt((float) $product->sell_price),
            'image' => $product->image
                ? url('storage/' . $product->image)
                : url('images/placeholder.png'),
            'url' => route('storefront.shop.show', ['slug' => $product->slug]),
        ])->all();

        $count = count($cards);
        $ids = implode(',', array_column($cards, 'id'));
        $primaryId = $cards[0]['id'];
        $primaryName = $cards[0]['name'];

        // Persist "what we just showed" in the session so the system prompt
        // can inject it into the NEXT turn's context. This is more reliable
        // than depending on the model to remember IDs across conversation
        // memory — Gemini in particular tends to default to the first ID
        // it ever saw in any earlier turn.
        session([
            'ai_last_shown_product_id' => $primaryId,
            'ai_last_shown_product_name' => $primaryName,
            'ai_last_shown_product_ids' => array_column($cards, 'id'),
        ]);

        $header = $usedFallback
            ? "No exact match for \"{$query}\". Showing {$count} alternatives. Product IDs: {$ids}."
            : "{$count} products. Product IDs: {$ids}. Most recently shown: {$primaryId}.";

        return $header . "\n<<PRODUCT_CARDS:" . json_encode($cards, JSON_UNESCAPED_UNICODE) . '>>';
    }

    protected function browseProducts(array $filters)
    {
        $query = Product::query()
            ->where('status', 'active')
            ->where('ecom_visible', true);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }
        if (isset($filters['min_price'])) {
            $query->where('sell_price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('sell_price', '<=', $filters['max_price']);
        }

        return $query->latest('id')->limit(8)->get();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Specific search query in Bangla, English, or Romanized Bangla / "Banglish" (e.g. "red shirt", "ছেলেদের পাঞ্জাবি", "lal jama"). Leave empty when browse=true.'),
            'browse' => $schema->boolean()
                ->description('Set to true when the user asks "what products do you have", "show me everything", "ki ki product ache" or similar generic browse questions. Returns the latest 8 products from the catalog instead of running a search.'),
            'category' => $schema->string()
                ->description('Optional category NAME to filter by (e.g. "Fashion", "Electronics", "Groceries"). Server resolves to id — prefer this over guessing category_id.'),
            'category_id' => $schema->integer()
                ->description('Optional category id. Use ONLY when you obtained the id from list_categories. Do NOT guess this.'),
            'brand_id' => $schema->integer()
                ->description('Optional: restrict to a brand by ID.'),
            'min_price' => $schema->number()
                ->description('Optional: minimum price in BDT.'),
            'max_price' => $schema->number()
                ->description('Optional: maximum price in BDT.'),
        ];
    }

    protected function formatBdt(float $amount): string
    {
        $isNegative = $amount < 0;
        $amount = abs($amount);
        $formatted = number_format($amount, 2, '.', '');
        [$whole, $decimal] = array_pad(explode('.', $formatted), 2, '00');

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($whole, 0, -3));
            $whole = $rest . ',' . $last3;
        }

        return ($isNegative ? '-' : '') . $whole . ($decimal !== '00' ? '.' . $decimal : '');
    }
}
