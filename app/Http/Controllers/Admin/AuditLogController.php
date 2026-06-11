<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = Activity::with('causer')->latest()->paginate(50);
        
        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs
        ]);
    }
}
