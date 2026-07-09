<?php

namespace App\Http\Controllers\Api\V1;

use App\Cms\ContentTypeDefinition;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CollectionEntryResource;
use App\Services\Api\V1\CollectionApiService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Headless read API for CMS collections (ТЗ §10.9, roadmap §7.4).
 */
class CollectionController extends Controller
{
    public function __construct(private CollectionApiService $collections) {}

    public function index(Request $request, string $collection): AnonymousResourceCollection
    {
        $type = $this->resolveType($collection);
        $request->attributes->set('api_collection', $type);
        $request->attributes->set('api_detailed', false);

        $query = $this->collections->query($type);
        $sort = $type->defaultSort;
        $direction = $type->defaultSortDirection === 'asc' ? 'asc' : 'desc';

        $entries = $query
            ->orderBy($sort, $direction)
            ->paginate($this->collections->perPage());

        return CollectionEntryResource::collection($entries)->additional([
            'meta' => [
                'collection' => $type->handle,
                'label' => $type->label,
            ],
        ]);
    }

    public function show(Request $request, string $collection, string $slug): CollectionEntryResource
    {
        $type = $this->resolveType($collection);
        $request->attributes->set('api_collection', $type);
        $request->attributes->set('api_detailed', true);

        $entry = $this->collections->findBySlug($type, $slug);

        if ($entry === null) {
            throw new NotFoundHttpException;
        }

        return new CollectionEntryResource($entry);
    }

    private function resolveType(string $collection): ContentTypeDefinition
    {
        try {
            return $this->collections->resolveType($collection);
        } catch (\InvalidArgumentException) {
            throw new NotFoundHttpException;
        }
    }
}
