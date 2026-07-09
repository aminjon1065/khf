<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ContentBrowserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, ContentBrowserService $browser): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        return response()->json([
            'results' => $browser->searchAcrossCollections($request->user(), $query),
        ]);
    }
}
