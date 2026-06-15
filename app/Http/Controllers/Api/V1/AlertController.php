<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AlertResource;
use App\Models\Alert;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Active emergency alerts for the internal API (ТЗ §6.4, §10.9). Read-only; only currently-active
 * alerts (published, within their time window) are exposed.
 */
class AlertController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $alerts = Alert::active()
            ->with(['translations', 'region.translations'])
            ->orderByDesc('starts_at')
            ->get();

        return AlertResource::collection($alerts);
    }
}
