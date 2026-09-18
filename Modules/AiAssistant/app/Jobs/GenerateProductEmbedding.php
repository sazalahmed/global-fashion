<?php

namespace Modules\AiAssistant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AiAssistant\Services\EmbeddingService;
use Modules\Product\Models\Product;

class GenerateProductEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(public int $productId) {}

    public function handle(EmbeddingService $service): void
    {
        $product = Product::query()->find($this->productId);

        if (! $product) {
            return;
        }

        $service->generate($product);
    }

    public function uniqueId(): string
    {
        return 'product-embedding-' . $this->productId;
    }
}
