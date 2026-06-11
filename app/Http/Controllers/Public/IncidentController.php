<?php

namespace App\Http\Controllers\Public;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Cache;

class IncidentController extends Controller
{
    /**
     * Public incidents archive / operational situation (ТЗ §5, §6.3): a summary of the current
     * situation (counts by status) above the archive, active events first.
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $cacheKeyVersion = Incident::max('updated_at') ?? 'empty';
        
        $counts = Cache::remember('incidents.summary.'.$cacheKeyVersion, 3600, function () {
            return Incident::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        });

        $summary = [
            'active' => (int) ($counts[IncidentStatus::Active->value] ?? 0),
            'controlled' => (int) ($counts[IncidentStatus::Controlled->value] ?? 0),
            'resolved' => (int) ($counts[IncidentStatus::Resolved->value] ?? 0),
        ];

        // No locale filter here: active incidents must never be hidden from a locale, and this keeps
        // the list consistent with the unfiltered summary counts above (translation() falls back).
        $page = request('page', 1);
        $incidentsCacheKey = 'incidents.archive.'.$locale.'.page.'.$page.'.'.$cacheKeyVersion;
        
        $incidents = Cache::remember($incidentsCacheKey, 3600, function () use ($locale) {
            return Incident::query()
                ->with(['translations', 'region.translations'])
                ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'controlled' THEN 1 ELSE 2 END")
                ->orderByDesc('occurred_at')
                ->paginate(20)
                ->through(function (Incident $incident) use ($locale): array {
                    $translation = $incident->translation($locale);

                    return [
                        'title' => $translation?->title,
                        'description' => $translation?->description,
                        'type_label' => $incident->type->label(),
                        'hazard_level' => $incident->hazard_level->value,
                        'hazard_label' => $incident->hazard_level->label(),
                        'status' => $incident->status->value,
                        'status_label' => $incident->status->label(),
                        'region' => $incident->region?->translation($locale)?->name,
                        'occurred_at' => $incident->occurred_at?->format('d.m.Y H:i'),
                    ];
                });
        });

        return Inertia::render('public/incidents/index', [
            'incidents' => $incidents,
            'summary' => $summary,
        ]);
    }
}
