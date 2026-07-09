<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Post-authentication landing: CMS staff go to the admin panel, everyone else to profile settings.
 */
class AuthenticatedHomeController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = request()->user();

        if ($user?->hasAnyRole(Role::values())) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('profile.edit');
    }
}
