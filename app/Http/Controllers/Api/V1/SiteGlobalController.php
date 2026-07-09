<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Cms\GlobalResolver;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Headless read API for CMS globals (Statamic globals analogue).
 */
class SiteGlobalController extends Controller
{
    public function __construct(private GlobalResolver $globals) {}

    public function show(string $handle): JsonResponse
    {
        $definition = $this->globals->definition($handle);

        if ($definition === null) {
            throw new NotFoundHttpException;
        }

        return response()->json([
            'handle' => $handle,
            'label' => $definition->label,
            'locale' => app()->getLocale(),
            'data' => $this->globals->resolve($handle),
        ]);
    }
}
