<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * The CMS dashboard — an operational overview for staff (ТЗ §7).
     */
    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/dashboard',
            $this->dashboard->payload($request->user(), app()->getLocale()),
        );
    }
}
