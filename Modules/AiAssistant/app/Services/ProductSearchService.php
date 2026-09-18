<?php

namespace Modules\AiAssistant\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\AiAssistant\Models\AiProductEmbedding;
use Modules\AiAssistant\Models\AiSearchLog;
use Modules\Product\Models\Product;

class ProductSearchService
{
    protected const FUSION_K = 60;

    protected const EMBEDDINGS_CACHE_KEY = 'ai_all_product_embeddings';

    protected const EMBEDDINGS_CACHE_TTL_SECONDS = 3600;

    public function __construct(
        protected EmbeddingService $embeddings,
        protected AiProviderResolver $resolver,
    ) {}

    public function search(string $query, array $filters = [], int $limit = 10): Collection
    {
        $startedAt = microtime(true);
        $query = trim($query);

        if (blank($query)) {
            return collect();
        }

        $vectorHits = $this->vectorSearch($query, 20);
        $keywordHits = $this->keywordSearch($query, 20);

        $fused = $this->fuse($vectorHits, $keywordHits);

        $productIds = $fused->pluck('product_id')->all();

        if (empty($productIds)) {
            $this->logSearch($query, $vectorHits, $keywordHits, collect(), $startedAt);
            return collect();
        }

        $products = $this->loadProductsWithFilters($productIds, $filters);

        $ordered = collect($productIds)
            ->map(fn ($id) => $products->firstWhere('id', $id))
            ->filter()
            ->take($limit)
            ->values();

        $this->logSearch($query, $vectorHits, $keywordHits, $ordered, $startedAt);

        return $ordered;
    }

    protected function vectorSearch(string $query, int $limit): Collection
    {
        $queryVector = $this->embeddings->embedQuery($query);

        if ($queryVector === null) {
            return collect();
        }

        $provider = $this->resolver->embeddingLab()->value;
        $model = $this->resolver->embeddingModel();

        $rows = Cache::remember(
            self::EMBEDDINGS_CACHE_KEY . ':' . $provider . ':' . $model,
            self::EMBEDDINGS_CACHE_TTL_SECONDS,
            fn () => AiProductEmbedding::query()
                ->forProvider($provider, $model)
                ->get(['product_id', 'embedding'])
                ->map(fn ($row) => [
                    'product_id' => $row->product_id,
                    'embedding' => $row->embedding,
                ])
                ->all()
        );

        $scored = collect($rows)
            ->map(fn ($row) => [
                'product_id' => $row['product_id'],
                'score' => $this->cosineSimilarity($queryVector, $row['embedding']),
            ])
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return $scored;
    }

    protected function keywordSearch(string $query, int $limit): Collection
    {
        $booleanQuery = $this->toBooleanQuery($query);

        if (blank($booleanQuery)) {
            return collect();
        }

        return collect(DB::table('products')
            ->select('id as product_id')
            ->selectRaw(
                'MATCH(name, sku, description, long_description) AGAINST(? IN BOOLEAN MODE) as score',
                [$booleanQuery]
            )
            ->whereRaw(
                'MATCH(name, sku, description, long_description) AGAINST(? IN BOOLEAN MODE)',
                [$booleanQuery]
            )
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => (int) $row->product_id,
                'score' => (float) $row->score,
            ])
            ->all());
    }

    protected function fuse(Collection $vectorHits, Collection $keywordHits): Collection
    {
        $scores = [];
        $boost = []; // tracks whether a candidate hit BOTH arms

        foreach ($vectorHits->values() as $rank => $hit) {
            $id = $hit['product_id'];
            $scores[$id] = ($scores[$id] ?? 0) + 1 / (self::FUSION_K + $rank + 1);
            $boost[$id] = ($boost[$id] ?? 0) | 1;
        }

        foreach ($keywordHits->values() as $rank => $hit) {
            $id = $hit['product_id'];
            $scores[$id] = ($scores[$id] ?? 0) + 1 / (self::FUSION_K + $rank + 1);
            $boost[$id] = ($boost[$id] ?? 0) | 2;
        }

        arsort($scores);

        // Relevance filtering. Two regimes:
        //
        //   1. Keyword arm fired (boost & 2 means the term literally appears
        //      in the product text): trust those as high-confidence. Also
        //      keep vector-only candidates that scored ≥ 66% of the top.
        //
        //   2. Keyword arm was empty (typically because the catalog is in
        //      English but the customer query is in Bangla/script — e.g.
        //      "ল্যাপটপ" vs "ProBook 15 Laptop"). Vector arm scored every
        //      product against the query, so noise is high. Be strict:
        //      keep only the top 3 candidates, AND require ≥ 75% of top.
        //      This stops the "9 unrelated products" spam from a low-
        //      confidence semantic match.
        $hasKeywordHits = $keywordHits->isNotEmpty();
        $top = $scores ? reset($scores) : 0;
        $vectorOnlyCutoff = $hasKeywordHits ? 0.66 : 0.85;
        // When the keyword arm misses, the catalog likely doesn't share the
        // customer's writing system (e.g. Bangla query against an English-
        // only catalog). Trust only the SINGLE highest-confidence vector
        // match — anything beyond that is semantic noise.
        $maxResults = $hasKeywordHits ? PHP_INT_MAX : 1;

        $filtered = collect($scores)
            ->filter(function ($score, $id) use ($boost, $top, $vectorOnlyCutoff) {
                if (($boost[$id] ?? 0) & 2) {
                    return true; // keyword hit — always keep
                }
                return $score >= ($top * $vectorOnlyCutoff);
            })
            ->take($maxResults)
            ->map(fn ($score, $id) => ['product_id' => (int) $id, 'score' => $score])
            ->values();

        return $filtered;
    }

    protected function loadProductsWithFilters(array $productIds, array $filters): Collection
    {
        $query = Product::query()
            ->whereIn('id', $productIds)
            ->where('status', 'active');

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

        if (! empty($filters['ecom_only'])) {
            $query->where('ecom_visible', true);
        }

        return $query->get();
    }

    protected function cosineSimilarity(array $a, array $b): float
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

        $denominator = sqrt($magA) * sqrt($magB);

        return $denominator > 0 ? $dot / $denominator : 0.0;
    }

    protected function toBooleanQuery(string $query): string
    {
        $tokens = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $cleaned = array_filter(array_map(function ($token) {
            $token = preg_replace('/[+\-><()~*"@]/u', '', $token);
            return mb_strlen($token) >= 2 ? $token : null;
        }, $tokens));

        if (empty($cleaned)) {
            return '';
        }

        return implode(' ', array_map(fn ($t) => $t . '*', $cleaned));
    }

    protected function logSearch(
        string $query,
        Collection $vectorHits,
        Collection $keywordHits,
        Collection $results,
        float $startedAt,
    ): void {
        try {
            AiSearchLog::create([
                'query' => mb_substr($query, 0, 500),
                'vector_top_id' => $vectorHits->first()['product_id'] ?? null,
                'keyword_top_id' => $keywordHits->first()['product_id'] ?? null,
                'fused_top_id' => $results->first()?->id,
                'result_ids' => $results->pluck('id')->all(),
                'total_results' => $results->count(),
                'latency_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function flushEmbeddingsCache(): void
    {
        $keys = Cache::get('ai_embedding_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('ai_embedding_cache_keys');
    }
}
