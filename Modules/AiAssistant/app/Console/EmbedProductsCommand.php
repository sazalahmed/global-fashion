<?php

namespace Modules\AiAssistant\Console;

use Illuminate\Console\Command;
use Modules\AiAssistant\Services\AiProviderResolver;
use Modules\AiAssistant\Services\EmbeddingService;
use Modules\Product\Models\Product;

class EmbedProductsCommand extends Command
{
    protected $signature = 'ai:embed-products
                            {--chunk=100 : Number of products per chunk}
                            {--force : Re-embed even if content_hash is unchanged}';

    protected $description = 'Backfill embeddings for all active products via the configured embedding provider.';

    public function handle(EmbeddingService $embeddings, AiProviderResolver $resolver): int
    {
        $provider = $resolver->embeddingLab()->value;
        $model = $resolver->embeddingModel();
        $dimensions = $resolver->embeddingDimensions();

        $this->info("Using provider: {$provider} | model: {$model} | dimensions: {$dimensions}");

        $total = Product::query()->where('status', 'active')->count();

        if ($total === 0) {
            $this->warn('No active products found.');
            return self::SUCCESS;
        }

        $this->info("Embedding {$total} products...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        if ($this->option('force')) {
            $this->warn('--force flag set: existing embeddings will be regenerated.');
        }

        $generated = $embeddings->backfillAll(
            chunkSize: (int) $this->option('chunk'),
            progress: fn () => $bar->advance(),
        );

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. {$generated}/{$total} embeddings generated or up-to-date.");

        return self::SUCCESS;
    }
}
