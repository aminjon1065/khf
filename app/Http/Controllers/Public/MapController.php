<?php

namespace App\Http\Controllers\Public;

use App\Enums\HazardLevel;
use App\Enums\IncidentType;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    /**
     * Public interactive incident map (ТЗ §6.3): active events with coordinates.
     */
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();

        $query = Incident::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['translations', 'region.translations']);

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('level')) {
            $query->where('hazard_level', $request->query('level'));
        }

        if ($request->filled('region')) {
            $query->where('region_id', $request->query('region'));
        }

        if ($request->filled('period')) {
            $period = $request->query('period');
            if ($period === 'today') {
                $query->whereDate('occurred_at', today());
            } elseif ($period === 'week') {
                $query->where('occurred_at', '>=', now()->subWeek());
            } elseif ($period === 'month') {
                $query->where('occurred_at', '>=', now()->subMonth());
            }
        }

        $incidents = $query->get()->map(function (Incident $incident) use ($locale): array {
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
        })->all();

        return Inertia::render('public/map', [
            'incidents' => $incidents,
            'filters' => $request->only(['type', 'level', 'region', 'period']),
            'types' => collect(IncidentType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])->all(),
            'levels' => collect(HazardLevel::cases())->map(fn ($l) => ['value' => $l->value, 'label' => $l->label()])->all(),
            'regions' => Region::with('translations')->get()->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->translation($locale)?->name])->all(),
        ]);
    }
}
