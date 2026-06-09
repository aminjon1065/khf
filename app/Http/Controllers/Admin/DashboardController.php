<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * The CMS dashboard — an at-a-glance overview for staff (ТЗ §7).
     */
    public function index(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'users' => User::count(),
                'languages' => Language::where('is_active', true)->count(),
                'roles' => Role::count(),
            ],
        ]);
    }
}
