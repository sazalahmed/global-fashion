<?php

namespace App\Services\Search;

use App\Contracts\SearchableInterface;
use Illuminate\Support\Facades\Auth;

class SearchableRegistry
{
    /** @var array<string, class-string<SearchableInterface>> */
    private array $models = [];

    /**
     * Register a searchable model class.
     */
    public function register(string $modelClass): void
    {
        if (!is_subclass_of($modelClass, SearchableInterface::class)) {
            return;
        }

        $type = $modelClass::getSearchType();
        $this->models[$type] = $modelClass;
    }

    /**
     * Get all registered model classes.
     *
     * @return array<string, class-string<SearchableInterface>>
     */
    public function all(): array
    {
        return $this->models;
    }

    /**
     * Get only models the current user has permission to search.
     *
     * @return array<string, class-string<SearchableInterface>>
     */
    public function authorized(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        return array_filter($this->models, function (string $modelClass) use ($user) {
            $permission = $modelClass::getSearchPermission();

            return $permission === null || $user->can($permission);
        });
    }

    /**
     * Get sorted model classes by their search order.
     *
     * @return array<string, class-string<SearchableInterface>>
     */
    public function authorizedSorted(): array
    {
        $models = $this->authorized();

        uasort($models, function (string $a, string $b) {
            return $a::getSearchOrder() <=> $b::getSearchOrder();
        });

        return $models;
    }
}
