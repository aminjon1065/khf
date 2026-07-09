<?php

namespace App\Http\Middleware;

use App\Support\RedirectResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyRedirects
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $match = RedirectResolver::match($request);

        if ($match !== null) {
            return redirect($match['to'], $match['status']);
        }

        return $next($request);
    }
}
