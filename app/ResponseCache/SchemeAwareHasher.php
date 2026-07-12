<?php

namespace App\ResponseCache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\DefaultHasher;

/**
 * Includes the request scheme in the cache key so https://khf.test and http://khf.test
 * do not share a cached HTML body (absolute Vite/asset URLs would otherwise cause Mixed Content).
 */
class SchemeAwareHasher extends DefaultHasher
{
    public function getHashFor(Request $request): string
    {
        $scheme = $request->headers->get('X-Forwarded-Proto', $request->getScheme());

        return hash('xxh128', $scheme.'-'.parent::getHashFor($request));
    }
}
