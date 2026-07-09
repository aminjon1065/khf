<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Middlewares\CacheResponse as SpatieCacheResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spatie's default middleware serves any cached GET response to every client, including
 * Inertia XHR visits. That returns full HTML instead of an Inertia JSON payload, which
 * triggers Inertia's error dialog (sandboxed srcdoc iframe) and cascades into CORS
 * errors loading Vite chunks from a null origin.
 */
class CacheResponse extends SpatieCacheResponse
{
    protected function getCachedResponse(Request $request, array $tags): ?Response
    {
        if ($this->shouldBypassCacheLookup($request)) {
            return null;
        }

        return parent::getCachedResponse($request, $tags);
    }

    private function shouldBypassCacheLookup(Request $request): bool
    {
        return $request->ajax() || $request->headers->has('X-Inertia');
    }
}
