<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Cms\TaxonomyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TaxonomyController extends Controller
{
    public function __construct(private TaxonomyService $taxonomies) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->taxonomies->catalog(),
        ]);
    }

    public function show(Request $request, string $handle): JsonResponse
    {
        try {
            $terms = $this->taxonomies->items($handle, $request->query('locale'));
        } catch (InvalidArgumentException) {
            abort(404, "Unknown taxonomy [{$handle}].");
        }

        return response()->json([
            'handle' => $handle,
            'terms' => $terms,
        ]);
    }
}
