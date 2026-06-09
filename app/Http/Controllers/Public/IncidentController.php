<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    /**
     * Public incidents archive / operational situation (ТЗ §5, §6.3): active events first.
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $incidents = Incident::query()
            ->with(['translations', 'region.translations'])
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'controlled' THEN 1 ELSE 2 END")
            ->orderByDesc('occurred_at')
            ->paginate(20)
            ->through(function (Incident $incident) use ($locale): array {
                $translation = $incident->translation($locale);

                return [
                    'title' => $translation?->title,
                    'description' => $translation?->description,
                    'type_label' => $incident->type->label(),
                    'hazard_label' => $incident->hazard_level->label(),
                    'hazard_color' => $incident->hazard_level->color(),
                    'status' => $incident->status->value,
                    'status_label' => $incident->status->label(),
                    'region' => $incident->region?->translation($locale)?->name,
                    'occurred_at' => $incident->occurred_at?->format('d.m.Y H:i'),
                ];
            });

        return Inertia::render('public/incidents/index', [
            'incidents' => $incidents,
        ]);
    }
}
