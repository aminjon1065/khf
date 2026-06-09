<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces users in privileged roles to have confirmed two-factor authentication before reaching
 * protected (CMS) areas (ТЗ §7.1, §12.3). Users who haven't enabled 2FA are redirected to the
 * security settings page to set it up.
 */
class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->hasAnyRole(Role::twoFactorRequired())
            && ! $user->hasEnabledTwoFactorAuthentication()
            && ! $request->routeIs('security.edit', 'two-factor.*', 'logout')
        ) {
            $message = __('Two-factor authentication is required for your role. Please enable it to continue.');

            if ($request->expectsJson()) {
                abort(403, $message);
            }

            return redirect()->route('security.edit')->with('status', $message);
        }

        return $next($request);
    }
}
