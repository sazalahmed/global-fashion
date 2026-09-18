<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Customer\Models\Area;
use Stringable;

/**
 * Lets the agent resolve fuzzy district inputs ("dhaka", "ঢাকা",
 * "Chittagong" vs "Chattogram") to the canonical names + IDs the
 * QuickCheckout tool expects. Cheap to call (<70 rows total).
 */
class ListDistricts implements Tool
{
    use LogsToolInvocation;

    public function description(): Stringable|string
    {
        return 'Look up Bangladesh districts by name fragment. Use to resolve fuzzy district inputs before checkout.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'list_districts');

        $query = trim((string) ($request['query'] ?? ''));

        $rows = Area::query()->where('level', 'district');
        if ($query !== '') {
            $rows->where('name', 'like', '%' . $query . '%');
        }

        $districts = $rows->orderBy('name')->limit(15)->get(['id', 'name'])->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->name,
        ])->all();

        if (empty($districts)) {
            return json_encode([
                'count' => 0,
                'message' => 'No matching district. Ask the user to spell it differently or pick from the 64 BD districts.',
            ]);
        }

        return json_encode([
            'count' => count($districts),
            'districts' => $districts,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional case-insensitive name fragment to filter by (e.g. "dha", "chit", "ঢাকা").'),
        ];
    }
}
