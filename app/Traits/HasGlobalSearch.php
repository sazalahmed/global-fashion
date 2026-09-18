<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasGlobalSearch
{
    /**
     * Default global search scope: OR-matches all searchable columns with LIKE.
     */
    public function scopeGlobalSearch(Builder $query, string $term): Builder
    {
        $columns = static::getSearchableColumns();

        return $query->where(function (Builder $q) use ($columns, $term) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', '%' . $term . '%');
            }
        });
    }

    /**
     * Default URL: uses the named route with the model's primary key.
     */
    public function getSearchUrl(): string
    {
        return route(static::getSearchRoute(), $this->getKey());
    }

    /**
     * Default order: 50 (middle priority).
     */
    public static function getSearchOrder(): int
    {
        return 50;
    }

    /**
     * Relations to eager-load when building search results.
     * Override in models whose getSearchSubtitle()/getSearchTitle() touch
     * relations, to avoid an N+1 query per result row. Default: none.
     *
     * @return array<int, string>
     */
    public static function getSearchWith(): array
    {
        return [];
    }
}
