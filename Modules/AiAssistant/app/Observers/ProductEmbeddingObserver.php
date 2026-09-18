<?php

namespace Modules\AiAssistant\Observers;

use Modules\AiAssistant\Jobs\GenerateProductEmbedding;
use Modules\AiAssistant\Services\AiProviderResolver;
use Modules\Product\Models\Product;

class ProductEmbeddingObserver
{
    public function __construct(protected AiProviderResolver $resolver) {}

    public function saved(Product $product): void
    {
        if (! $this->resolver->isEnabled()) {
            return;
        }

        $watched = ['name', 'sku', 'description', 'long_description', 'brand_id', 'category_id', 'sell_price', 'status'];

        if ($product->wasRecentlyCreated || $product->wasChanged($watched)) {
            GenerateProductEmbedding::dispatch($product->id);
        }
    }

    // Deletion cascade is handled by the FK constraint on ai_product_embeddings.
}
