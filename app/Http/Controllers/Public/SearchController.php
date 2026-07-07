<?php

namespace App\Http\Controllers\Public;

use App\Enums\SearchContentType;
use App\Http\Controllers\Controller;
use App\Services\Public\SearchService;
use App\Services\SystemLoadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * API endpoint for live search (Cmd+K modal).
     */
    public function api(Request $request, string $locale, SearchService $searchService): JsonResponse
    {
        $query = $request->input('q', '');

        if (SystemLoadService::isHighLoad()) {
            return response()->json([
                'data' => [],
                'error' => __('ui.search.disabled_high_load', [], $locale),
            ], 503);
        }

        $type = SearchContentType::tryFromRequest($request->string('type')->toString());
        $results = $searchService->search($query, $locale, 10, $type);

        return response()->json([
            'data' => $results,
        ]);
    }

    /**
     * Full search results page with pagination, type filter, and highlighting (ТЗ §6.10).
     */
    public function index(Request $request, string $locale, SearchService $searchService): Response
    {
        $query = (string) $request->string('q');
        $type = SearchContentType::tryFromRequest($request->string('type')->toString());
        $page = max(1, (int) $request->integer('page', 1));

        if (SystemLoadService::isHighLoad()) {
            abort(503, __('ui.search.disabled_high_load', [], $locale));
        }

        $paginator = $searchService->paginate($query, $locale, $type, $page, 20);

        return Inertia::render('public/search', [
            'query' => $query,
            'results' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'type' => $type?->value,
            ],
            'contentTypes' => SearchContentType::values(),
        ]);
    }
}
