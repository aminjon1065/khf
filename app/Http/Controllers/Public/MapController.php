<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    /**
     * Public interactive incident map (ТЗ §6.3): active events with coordinates.
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $incidents = Incident::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['translations', 'region.translations'])
            ->get()
            ->map(function (Incident $incident) use ($locale): array {
                return [
                    'id' => $incident->id,
                    'lat' => (float) $incident->latitude,
                    'lng' => (float) $incident->longitude,
                    'color' => $incident->hazard_level->color(),
                    'title' => $incident->translation($locale)?->title ?? '',
                    'type' => $incident->type->label(),
                    'level' => $incident->hazard_level->label(),
                    'status' => $incident->status->label(),
                    'region' => $incident->region?->translation($locale)?->name,
                    'occurred_at' => $incident->occurred_at?->format('d.m.Y H:i'),
                ];
            })
            ->all();

        return Inertia::render('public/map', [
            'incidents' => $incidents,
        ]);
    }
}
