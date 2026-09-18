<?php

namespace Modules\AiAssistant\Services;

use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Embeddings;
use Modules\AiAssistant\Models\AiProductEmbedding;
use Modules\AiAssistant\Models\AiRequestLog;
use Modules\Product\Models\Product;

class EmbeddingService
{
    public function __construct(protected AiProviderResolver $resolver) {}

    public function buildText(Product $product): string
    {
        $product->loadMissing(['brand', 'category', 'tags']);

        $parts = [
            $product->name,
            $product->sku,
            $product->brand?->name,
            $product->category?->name,
            $product->tags->pluck('name')->filter()->implode(', '),
            strip_tags((string) $product->description),
            strip_tags((string) $product->long_description),
            'Price: ' . currency_symbol() . ' ' . number_format((float) $product->sell_price, 2),
        ];

        return collect($parts)
            ->filter(fn ($p) => filled($p))
            ->implode(' | ');
    }

    public function contentHash(string $text): string
    {
        return hash('sha256', $text);
    }

    public function generate(Product $product): ?AiProductEmbedding
    {
        $text = $this->buildText($product);

        if (blank($text)) {
            return null;
        }

        $hash = $this->contentHash($text);
        $provider = $this->resolver->embeddingLab()->value;
        $model = $this->resolver->embeddingModel();
        $dimensions = $this->resolver->embeddingDimensions();

        $existing = AiProductEmbedding::query()
            ->where('product_id', $product->id)
            ->where('provider', $provider)
            ->where('model', $model)
            ->first();

        if ($existing && $existing->content_hash === $hash) {
            return $existing;
        }

        $startedAt = microtime(true);
        $vector = null;
        $success = false;
        $errorMessage = null;
        $tokens = 0;

        try {
            $response = Embeddings::for([$text])
                ->dimensions($dimensions)
                ->generate($this->resolver->embeddingLab(), $model);

            $vector = $response->embeddings[0] ?? null;
            $tokens = $response->totalTokens ?? 0;
            $success = $vector !== null;
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        AiRequestLog::create([
            'operation' => 'embedding',
            'provider' => $provider,
            'model' => $model,
            'prompt_tokens' => $tokens,
            'completion_tokens' => 0,
            'total_tokens' => $tokens,
            'latency_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'success' => $success,
            'error' => $errorMessage,
            'meta' => ['product_id' => $product->id],
            'created_at' => now(),
        ]);

        if (! $success) {
            return null;
        }

        return AiProductEmbedding::updateOrCreate(
            [
                'product_id' => $product->id,
                'provider' => $provider,
                'model' => $model,
            ],
            [
                'dimensions' => $dimensions,
                'content_hash' => $hash,
                'embedding' => $vector,
            ]
        );
    }

    public function embedQuery(string $query): ?array
    {
        $cacheKey = 'ai_query_emb:' . $this->resolver->embeddingLab()->value . ':' . md5($query);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($query) {
            $startedAt = microtime(true);
            $vector = null;
            $tokens = 0;
            $errorMessage = null;

            try {
                $response = Embeddings::for([$query])
                    ->dimensions($this->resolver->embeddingDimensions())
                    ->providerOptions(['task' => 'retrieval.query'])
                    ->generate(
                        $this->resolver->embeddingLab(),
                        $this->resolver->embeddingModel()
                    );

                $vector = $response->embeddings[0] ?? null;
                $tokens = $response->totalTokens ?? 0;
            } catch (\Throwable $e) {
                $errorMessage = $e->getMessage();
            }

            AiRequestLog::create([
                'operation' => 'embedding_query',
                'provider' => $this->resolver->embeddingLab()->value,
                'model' => $this->resolver->embeddingModel(),
                'prompt_tokens' => $tokens,
                'completion_tokens' => 0,
                'total_tokens' => $tokens,
                'latency_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'success' => $vector !== null,
                'error' => $errorMessage,
                'meta' => ['query' => mb_substr($query, 0, 200)],
                'created_at' => now(),
            ]);

            return $vector;
        });
    }

    public function backfillAll(int $chunkSize = 100, ?callable $progress = null): int
    {
        $count = 0;

        Product::query()
            ->where('status', 'active')
            ->chunkById($chunkSize, function ($products) use (&$count, $progress) {
                foreach ($products as $product) {
                    if ($this->generate($product)) {
                        $count++;
                    }

                    if ($progress) {
                        $progress($product);
                    }
                }
            });

        return $count;
    }
}
