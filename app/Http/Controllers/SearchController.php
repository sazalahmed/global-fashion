<?php

namespace App\Http\Controllers;

use App\Services\Search\GlobalSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $searchService,
    ) {}

    /**
     * Quick search for dropdown (limited results per type).
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < (int) config('search.min_query_length', 2)) {
            return response()->json(['results' => [], 'navigation' => [], 'total_count' => 0]);
        }

        return response()->json($this->searchService->quickSearch($query));
    }

    /**
     * Full search with optional type filter.
     */
    public function fullSearch(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));
        $type = $request->input('type');

        if (strlen($query) < (int) config('search.min_query_length', 2)) {
            return response()->json(['groups' => [], 'navigation' => [], 'total_count' => 0]);
        }

        return response()->json($this->searchService->fullSearch($query, $type));
    }

    /**
     * Full-page search results with optional entity-type filter.
     */
    public function results(Request $request): View
    {
        $query = trim($request->input('q', ''));
        $type = $request->input('type') ?: null;

        $data = ['groups' => [], 'navigation' => [], 'total_count' => 0];

        if (strlen($query) >= (int) config('search.min_query_length', 2)) {
            $data = $this->searchService->fullSearch($query, $type);
        }

        return view('search.results', [
            'query'      => $query,
            'activeType' => $type,
            'types'      => $this->searchService->availableTypes(),
            'groups'     => $data['groups'],
            'navigation' => $data['navigation'],
            'totalCount' => $data['total_count'],
        ]);
    }
}
