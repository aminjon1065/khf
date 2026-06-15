<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\IncidentResource;
use App\Models\Incident;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Active emergency incidents for the internal API (ТЗ §6.3, §10.9). Read-only; only unresolved
 * incidents (active or under control) are exposed, newest first, paginated.
 */
class IncidentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $incidents = Incident::active()
            ->with(['translations', 'region.translations'])
            ->orderByDesc('occurred_at')
            ->paginate(20);

        return IncidentResource::collection($incidents);
    }
}
