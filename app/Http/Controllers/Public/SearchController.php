<?php

namespace App\Http\Controllers\Public;

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

        $results = $searchService->search($query, $locale, 10);

        return response()->json([
            'data' => $results,
        ]);
    }

    /**
     * Full search results page.
     */
    public function index(Request $request, string $locale, SearchService $searchService): Response
    {
        $query = $request->input('q', '');

        if (SystemLoadService::isHighLoad()) {
            abort(503, __('ui.search.disabled_high_load', [], $locale));
        }

        $results = $query ? $searchService->search($query, $locale, 50) : collect();

        return Inertia::render('public/search', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
