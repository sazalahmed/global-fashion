<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\AiAssistant\Models\AiProductEmbedding;
use Modules\AiAssistant\Services\AiProviderResolver;
use Modules\Product\Models\Product;
use Stringable;

/**
 * Surface products similar to a reference product, via cosine
 * similarity over the existing ai_product_embeddings. Customer-facing
 * use cases:
 *   "anything similar to this?"
 *   "what goes with this?"
 *   "you might also like…"
 *
 * Emits a <<PRODUCT_CARDS:...>> block — frontend renders cards just
 * like search_products.
 */
class RecommendRelated implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected AiProviderResolver $resolver) {}

    public function description(): Stringable|string
    {
        return 'Find products similar to a reference product using vector similarity. '
            . 'Use for "anything similar to this?" or "what goes with this?" requests. '
            . 'Returns up to 4 cards.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'recommend_related');

        $productId = (int) ($request['product_id'] ?? 0);
        $limit = max(1, min(4, (int) ($request['limit'] ?? 4)));

        if ($productId <= 0) {
            return json_encode(['success' => false, 'message' => 'product_id is required.']);
        }

        $provider = $this->resolver->embeddingLab()->value;
        $model = $this->resolver->embeddingModel();

        $sourceEmbedding = AiProductEmbedding::query()
            ->forProvider($provider, $model)
            ->where('product_id', $productId)
            ->value('embedding');

        if (! $sourceEmbedding) {
            return json_encode([
                'success' => false,
                'message' => 'No embedding for that product yet. Run ai:embed-products and retry.',
            ]);
        }

        // Score every OTHER product against this one.
        $rows = AiProductEmbedding::query()
            ->forProvider($provider, $model)
            ->where('product_id', '!=', $productId)
            ->get(['product_id', 'embedding']);

        $scored = $rows->map(fn ($r) => [
            'product_id' => $r->product_id,
            'score' => $this->cosine($sourceEmbedding, $r->embedding),
        ])->sortByDesc('score')->take($limit)->values();

        $ids = $scored->pluck('product_id')->all();
        if (empty($ids)) {
            return json_encode(['success' => true, 'count' => 0, 'message' => 'No related products found.']);
        }

        $products = Product::query()
            ->whereIn('id', $ids)
            ->where('status', 'active')
            ->where('ecom_visible', true)
            ->get();

        $orderedIds = $scored->pluck('product_id')->all();
        $products = $products->sortBy(fn ($p) => array_search($p->id, $orderedIds))->values();

        if ($products->isEmpty()) {
            return json_encode(['success' => true, 'count' => 0, 'message' => 'No active matches.']);
        }

        $cards = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => currency_symbol() . ' ' . number_format((float) $p->sell_price, 2),
            'image' => $p->image ? url('storage/' . $p->image) : url('images/placeholder.png'),
            'url' => route('storefront.shop.show', ['slug' => $p->slug]),
        ])->all();

        return $products->count() . " similar product(s).\n"
            . '<<PRODUCT_CARDS:' . json_encode($cards, JSON_UNESCAPED_UNICODE) . '>>';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('Reference product whose similar items the customer wants.')
                ->required(),
            'limit' => $schema->integer()
                ->description('How many to return (1–4, default 4).'),
        ];
    }

    protected function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $magA += $a[$i] * $a[$i];
            $magB += $b[$i] * $b[$i];
        }
        $den = sqrt($magA) * sqrt($magB);
        return $den > 0 ? $dot / $den : 0.0;
    }
}
