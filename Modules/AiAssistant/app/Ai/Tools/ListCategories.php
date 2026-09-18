<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Category\Models\Category;
use Stringable;

/**
 * Look up product categories so the agent can correctly resolve
 * customer phrases like "fashion category", "electronics", "জামাকাপড়"
 * to actual category IDs before filtering search_products. Without
 * this the agent guesses category_id and routinely picks the wrong
 * bucket.
 */
class ListCategories implements Tool
{
    use LogsToolInvocation;

    public function description(): Stringable|string
    {
        return 'List product categories the store carries. Call this BEFORE '
            . 'using search_products with category_id, so you pass the correct id. '
            . 'Optional name fragment to filter ("fash" finds Fashion).';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'list_categories');

        $query = trim((string) ($request['query'] ?? ''));

        $rows = Category::query();
        if ($query !== '') {
            $rows->where('name', 'like', '%' . $query . '%');
        }

        $categories = $rows->orderBy('name')->limit(20)->get(['id', 'name', 'parent_id'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'parent_id' => $c->parent_id,
            ])->all();

        if (empty($categories)) {
            return json_encode([
                'count' => 0,
                'message' => 'No matching category. Use search_products without category_id and let the customer pick from the results.',
            ]);
        }

        return json_encode([
            'count' => count($categories),
            'categories' => $categories,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional case-insensitive name fragment (e.g. "fash", "groc", "জামা").'),
        ];
    }
}
