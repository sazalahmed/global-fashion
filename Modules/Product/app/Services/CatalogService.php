<?php

namespace Modules\Product\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\CatalogPosition;
use Modules\Product\Models\Product;

class CatalogService
{
    /** Allowed morph aliases for items in the unified catalog. */
    private const TYPES = ['product', 'combo'];

    /**
     * Merged, filtered, ordered, paginated list of products and combos.
     * Each returned item is a Product or Combo model carrying transient
     * `catalog_type` ('product'|'combo') and `catalog_position` (int|null)
     * attributes for the active ordering context.
     *
     * Filters: search, category (id), brand (id), status, stock, product_type,
     * type ('all'|'product'|'combo').
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $type = $filters['type'] ?? 'all';
        $categoryId = ! empty($filters['category']) ? (int) $filters['category'] : null;
        $subtreeIds = $categoryId ? $this->subtreeIds($categoryId) : null;

        $products = $type === 'combo' ? collect() : $this->products($filters, $subtreeIds);
        $combos = $type === 'product' ? collect() : $this->combos($filters, $subtreeIds);

        // Attach the active context's position to each item. Use concat (not
        // merge): products and combos can share integer ids, and Eloquent's
        // merge() keys by primary key, which would drop colliding rows.
        $positions = $this->positionsFor($categoryId);
        $merged = $products->values()->concat($combos->values())->map(function ($item) use ($positions) {
            $item->catalog_position = $positions[$item->catalog_type.':'.$item->id] ?? null;
            return $item;
        });

        // Positioned first (asc), then unpositioned by created_at desc.
        $sorted = $merged->sort(function ($x, $y) {
            $px = $x->catalog_position;
            $py = $y->catalog_position;
            if ($px !== null && $py !== null) {
                return $px <=> $py;
            }
            if ($px !== null) {
                return -1;
            }
            if ($py !== null) {
                return 1;
            }
            return $y->created_at <=> $x->created_at;
        })->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }

    /** @return array<int, int> category id + all active descendant ids */
    private function subtreeIds(int $categoryId): array
    {
        $category = Category::with('recursiveChildren')->find($categoryId);
        if (! $category) {
            return [$categoryId];
        }
        $ids = [$category->id];
        $walk = function ($node) use (&$walk, &$ids) {
            foreach ($node->recursiveChildren ?? [] as $child) {
                $ids[] = $child->id;
                $walk($child);
            }
        };
        $walk($category);

        return array_values(array_unique($ids));
    }

    private function products(array $filters, ?array $subtreeIds): Collection
    {
        $query = Product::with(['category', 'brand', 'unit', 'images'])
            ->withSum('warehouseStock', 'quantity');

        if ($subtreeIds !== null) {
            $query->where(function ($q) use ($subtreeIds) {
                $q->whereIn('category_id', $subtreeIds)
                    ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $subtreeIds));
            });
        }
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (! empty($filters['brand'])) {
            $query->byBrand((int) $filters['brand']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['product_type'])) {
            $query->byType($filters['product_type']);
        }
        if (! empty($filters['stock'])) {
            match ($filters['stock']) {
                'in_stock' => $query->inStock(),
                'low_stock' => $query->lowStock(),
                'out_of_stock' => $query->outOfStock(),
                default => null,
            };
        }

        return $query->get()->each(fn ($p) => $p->catalog_type = 'product');
    }

    private function combos(array $filters, ?array $subtreeIds): Collection
    {
        // Brand and stock filters do not apply to combos — exclude combos when set.
        if (! empty($filters['brand']) || ! empty($filters['stock'])) {
            return collect();
        }

        $query = Combo::with(['items.product', 'categories']);

        if ($subtreeIds !== null) {
            $query->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $subtreeIds));
        }
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->get()->each(fn ($c) => $c->catalog_type = 'combo');
    }

    /** @return array<string, int> "type:id" => position for the active context */
    private function positionsFor(?int $categoryId): array
    {
        $rows = CatalogPosition::query()
            ->when($categoryId === null, fn ($q) => $q->whereNull('category_id'))
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
            ->get(['positionable_type', 'positionable_id', 'position']);

        $out = [];
        foreach ($rows as $r) {
            $out[$r->positionable_type.':'.$r->positionable_id] = (int) $r->position;
        }

        return $out;
    }

    /**
     * Persist a new interleaved order for the given context.
     *
     * @param  int|null  $categoryId  viewing category id, or null for the global bucket
     * @param  array<int, array{type: string, id: int}>  $items  in display order
     */
    public function reorder(?int $categoryId, array $items): void
    {
        DB::transaction(function () use ($categoryId, $items) {
            $position = 0;
            foreach ($items as $item) {
                $type = $item['type'] ?? null;
                $id = (int) ($item['id'] ?? 0);
                if (! in_array($type, self::TYPES, true) || $id <= 0) {
                    continue;
                }
                $position++;
                CatalogPosition::updateOrCreate(
                    [
                        'category_id' => $categoryId,
                        'positionable_type' => $type,
                        'positionable_id' => $id,
                    ],
                    ['position' => $position],
                );
            }
        });
    }
}
