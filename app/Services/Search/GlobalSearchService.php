<?php

namespace App\Services\Search;

class GlobalSearchService
{
    public function __construct(
        private readonly SearchableRegistry $registry,
        private readonly SearchResultBuilder $builder,
        private readonly NavigationRegistry $navigation,
    ) {}

    /**
     * Quick search for dropdown: limited results per type.
     *
     * @return array{results: array, navigation: array, total_count: int}
     */
    public function quickSearch(string $term): array
    {
        $limit = (int) config('search.quick_limit', 5);
        $navLimit = (int) config('search.nav_limit', 5);

        $results = [];
        $totalCount = 0;

        foreach ($this->registry->authorizedSorted() as $type => $modelClass) {
            $group = $this->searchType($type, $modelClass, $term, $limit);

            if ($group === null) {
                continue;
            }

            $results[] = $group;
            $totalCount += $group['count'];
        }

        // Navigation search
        $navItems = $this->navigation->search($term, $navLimit);
        $navResults = [];
        foreach ($navItems as $navItem) {
            $navResults[] = $this->builder->buildNavigation($navItem, $term);
        }

        return [
            'results'     => $results,
            'navigation'  => $navResults,
            'total_count' => $totalCount + count($navResults),
        ];
    }

    /**
     * Full search with optional type filter: more results per type.
     *
     * @return array{groups: array, navigation: array, total_count: int}
     */
    public function fullSearch(string $term, ?string $filterType = null): array
    {
        $limit = (int) config('search.full_limit', 50);
        $navLimit = (int) config('search.nav_limit', 10);

        $groups = [];
        $totalCount = 0;

        foreach ($this->registry->authorizedSorted() as $type => $modelClass) {
            if ($filterType !== null && $type !== $filterType) {
                continue;
            }

            $group = $this->searchType($type, $modelClass, $term, $limit);

            if ($group === null) {
                continue;
            }

            $groups[] = $group;
            $totalCount += $group['count'];
        }

        // Navigation search (unless filtering by a specific entity type)
        $navResults = [];
        if ($filterType === null) {
            $navItems = $this->navigation->search($term, $navLimit);
            foreach ($navItems as $navItem) {
                $navResults[] = $this->builder->buildNavigation($navItem, $term);
            }
        }

        return [
            'groups'      => $groups,
            'navigation'  => $navResults,
            'total_count' => $totalCount + count($navResults),
        ];
    }

    /**
     * Entity types the current user may search, mapped to their icon.
     * Used to render type-filter chips on the full results page.
     *
     * @return array<string, string>
     */
    public function availableTypes(): array
    {
        $types = [];

        foreach ($this->registry->authorizedSorted() as $type => $modelClass) {
            $types[$type] = $modelClass::getSearchIcon();
        }

        return $types;
    }

    /**
     * Search a single entity type and build its result group.
     * Eager-loads relations declared via getSearchWith() to avoid N+1.
     *
     * @param  class-string  $modelClass
     * @return array{type: string, icon: string, count: int, items: array}|null
     */
    private function searchType(string $type, string $modelClass, string $term, int $limit): ?array
    {
        $query = $modelClass::query()->globalSearch($term)->limit($limit);

        if (method_exists($modelClass, 'getSearchWith')) {
            $with = $modelClass::getSearchWith();
            if (!empty($with)) {
                $query->with($with);
            }
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            return null;
        }

        $formatted = [];
        foreach ($items as $item) {
            $formatted[] = $this->builder->build($item, $term);
        }

        return [
            'type'  => $type,
            'icon'  => $modelClass::getSearchIcon(),
            'count' => count($formatted),
            'items' => $formatted,
        ];
    }
}
