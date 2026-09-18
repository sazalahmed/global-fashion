<?php

namespace Modules\Category\Services;

use App\Helpers\Upload;
use Modules\Category\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Get paginated list of categories with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Category::with('parent')
            ->withCount('children')
            ->ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Full category hierarchy flattened into tree (depth-first) order for the
     * listing page. Every row carries a `depth` (0 = root) so the view can
     * indent children beneath their parent.
     *
     * No pagination — the whole tree is returned so families never split.
     * When a search/status/parent filter is supplied the list is narrowed to
     * the matching nodes, but their ancestors are kept so indentation always
     * has visible context.
     */
    public function tree(array $filters = []): Collection
    {
        $all = Category::with('parent')->ordered()->get();
        $byId = $all->keyBy('id');

        // Group children under their parent id (roots keyed by 0).
        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?? 0);

        // Total descendants (all levels) under a node — used to decide whether a
        // root's sub-categories are numerous enough to warrant separators.
        $descendantCount = function ($parentId) use (&$descendantCount, $byParent) {
            $total = 0;
            foreach ($byParent->get($parentId, collect()) as $child) {
                $total += 1 + $descendantCount($child->id);
            }
            return $total;
        };

        $flat = collect();
        $walk = function ($parentId, int $depth) use (&$walk, &$flat, $byParent, $descendantCount) {
            $index = 0;
            foreach ($byParent->get($parentId, collect()) as $cat) {
                $cat->depth = $depth;

                // Visual divider between the top-level (depth-1) sub-categories of
                // a root that has more than 2 total sub-categories. Never before
                // the first child, and never at deeper levels.
                $cat->tree_separator = $depth === 1
                    && $index > 0
                    && $descendantCount($cat->parent_id) > 2;

                $flat->push($cat);
                $walk($cat->id, $depth + 1);
                $index++;
            }
        };
        $walk(0, 0);

        return $this->applyTreeFilters($flat, $byId, $filters);
    }

    /**
     * Narrow the flattened tree to nodes matching the filters, keeping each
     * match's ancestor chain so the hierarchy reads correctly.
     */
    private function applyTreeFilters(Collection $flat, Collection $byId, array $filters): Collection
    {
        $search   = $filters['search'] ?? null;
        $status   = !empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)
            ? $filters['status'] : null;
        $parentId = !empty($filters['parent_id']) ? (int) $filters['parent_id'] : null;

        if (! $search && ! $status && ! $parentId) {
            return $flat;
        }

        $matched = $flat->filter(function ($cat) use ($search, $status, $parentId, $byId) {
            if ($search) {
                $needle = mb_strtolower($search);
                $hit = str_contains(mb_strtolower($cat->name), $needle)
                    || str_contains(mb_strtolower((string) $cat->slug), $needle);
                if (! $hit) return false;
            }
            if ($status && $cat->status !== $status) return false;
            if ($parentId && ! $this->isInSubtree($cat, $parentId, $byId)) return false;
            return true;
        });

        // Include every match's ancestors so indented rows keep their context.
        $visibleIds = [];
        foreach ($matched as $cat) {
            $cursor = $cat;
            while ($cursor) {
                $visibleIds[$cursor->id] = true;
                $cursor = $cursor->parent_id ? $byId->get($cursor->parent_id) : null;
            }
        }

        return $flat->filter(fn ($cat) => isset($visibleIds[$cat->id]))->values();
    }

    /**
     * Whether $cat is the category $ancestorId itself or sits anywhere in its
     * descendant subtree (walks up the parent chain).
     */
    private function isInSubtree($cat, int $ancestorId, Collection $byId): bool
    {
        $cursor = $cat;
        while ($cursor) {
            if ((int) $cursor->id === $ancestorId) return true;
            $cursor = $cursor->parent_id ? $byId->get($cursor->parent_id) : null;
        }
        return false;
    }

    /**
     * Flattened category tree with indent prefixes for parent-selector dropdowns.
     *
     * Returns ALL active categories at any depth (not just roots) so users
     * can build 3+ level hierarchies. `name` is decorated with em-dashes
     * matching the depth (e.g. "— Mobile Phones" sits under "Electronics").
     *
     * On edit, the category itself AND its entire descendant subtree are
     * excluded so a user can't make a category a child of one of its own
     * descendants (which would create a cycle).
     */
    public function getParentOptions(?int $excludeId = null): Collection
    {
        $roots = Category::active()
            ->root()
            ->ordered()
            ->with('recursiveChildren')
            ->get();

        $excludedIds = $excludeId
            ? $this->collectSubtreeIds(Category::with('recursiveChildren')->find($excludeId))
            : [];

        $flat = collect();
        $walk = function ($categories, int $depth) use (&$walk, &$flat, $excludedIds) {
            foreach ($categories as $cat) {
                if (in_array($cat->id, $excludedIds, true)) continue;
                $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
                $flat->push((object) [
                    'id'   => $cat->id,
                    'name' => $prefix . $cat->name,
                ]);
                if ($cat->recursiveChildren && $cat->recursiveChildren->count()) {
                    $walk($cat->recursiveChildren, $depth + 1);
                }
            }
        };
        $walk($roots, 0);

        return $flat;
    }

    /**
     * Recursively collect a category's id + every descendant's id. Used to
     * exclude an entire subtree from the parent-selector on edit.
     */
    private function collectSubtreeIds(?Category $category): array
    {
        if (! $category) return [];
        $ids = [$category->id];
        foreach ($category->recursiveChildren ?? [] as $child) {
            $ids = array_merge($ids, $this->collectSubtreeIds($child));
        }
        return $ids;
    }

    /**
     * Create a new category.
     */
    public function create(array $data): Category
    {
        if (!empty($data['image'])) {
            $data['image'] = Upload::store($data['image'], 'categories');
        }

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        return Category::create($data);
    }

    /**
     * Update an existing category.
     */
    public function update(Category $category, array $data): Category
    {
        if (!empty($data['image'])) {
            if ($category->image) {
                Upload::delete($category->image);
            }
            $data['image'] = Upload::store($data['image'], 'categories');
        } elseif (!empty($data['remove_image'])) {
            if ($category->image) {
                Upload::delete($category->image);
            }
            $data['image'] = null;
        }

        unset($data['remove_image']);

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $category->id);
        }

        $category->update($data);

        return $category;
    }

    /**
     * Toggle a category's status between 'active' and 'inactive'.
     */
    public function toggleStatus(Category $category): Category
    {
        $category->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active',
        ]);

        return $category;
    }

    /**
     * Soft-delete a category, reassigning its children to its parent.
     */
    public function delete(Category $category): bool
    {
        if ($category->children()->count() > 0) {
            $category->children()->update(['parent_id' => $category->parent_id]);
        }

        if ($category->image) {
            Upload::delete($category->image);
        }

        return $category->delete();
    }

    /**
     * Get aggregate stats for the category dashboard.
     */
    public function getStats(): array
    {
        return [
            'total' => Category::count(),
            'active' => Category::where('status', 'active')->count(),
            'inactive' => Category::where('status', 'inactive')->count(),
            'withProducts' => 0, // Will be implemented in Phase 2
        ];
    }

    /**
     * Persist a new ordering. Each id's position in the array becomes its sort_order.
     */
    public function reorder(array $orderedIds): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Category::where('id', $id)->update(['sort_order' => $index + 1]);
            }
        });
    }

    /**
     * Generate a unique slug from a name, optionally excluding a specific category ID.
     */
    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        $query = Category::withTrashed()->where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $original . '-' . $count++;
            $query = Category::withTrashed()->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}
