<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface SearchableInterface
{
    /**
     * Human-readable label for this entity type (e.g. "Product", "Sale").
     */
    public static function getSearchType(): string;

    /**
     * FontAwesome 6 solid icon class (e.g. "fa-boxes-stacked").
     */
    public static function getSearchIcon(): string;

    /**
     * Named route for viewing a single record (e.g. "products.show").
     */
    public static function getSearchRoute(): string;

    /**
     * Permission required to search this entity. Null = no restriction.
     */
    public static function getSearchPermission(): ?string;

    /**
     * Columns to search against (e.g. ['name', 'sku', 'barcode']).
     */
    public static function getSearchableColumns(): array;

    /**
     * Display order priority (lower = appears first).
     */
    public static function getSearchOrder(): int;

    /**
     * Title text for a search result row.
     */
    public function getSearchTitle(): string;

    /**
     * Subtitle text for a search result row.
     */
    public function getSearchSubtitle(): string;

    /**
     * URL for this search result.
     */
    public function getSearchUrl(): string;

    /**
     * Scope to apply the global search query.
     */
    public function scopeGlobalSearch(Builder $query, string $term): Builder;
}
