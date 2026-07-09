<?php

namespace App\ResponseCache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

class PublicCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        if ($request->headers->has('X-Inertia')) {
            return false;
        }

        return parent::shouldCacheRequest($request);
    }
}
