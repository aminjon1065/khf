<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyRedirects
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $redirects = config('redirects', []);
        
        $path = $request->path();
        
        if (array_key_exists($path, $redirects)) {
            return redirect($redirects[$path], 301);
        }
        
        // Also check with a leading slash just in case
        $pathWithSlash = '/' . ltrim($path, '/');
        if (array_key_exists($pathWithSlash, $redirects)) {
            return redirect($redirects[$pathWithSlash], 301);
        }

        return $next($request);
    }
}
