<?php

namespace App\Http\Controllers\Public;

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Region;
use App\Services\SystemLoadService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

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
                ->pluck('total', 'status')
                ->all();
        });

        $summary = [
            'active' => (int) ($counts[IncidentStatus::Active->value] ?? 0),
            'controlled' => (int) ($counts[IncidentStatus::Controlled->value] ?? 0),
            'resolved' => (int) ($counts[IncidentStatus::Resolved->value] ?? 0),
        ];

        // No locale filter here: active incidents must never be hidden from a locale, and this keeps
        // the list consistent with the unfiltered summary counts above (translation() falls back).
        // Whitelist every filter to its known option set and clamp the page so an attacker can't
        // explode the cache-key space (unbounded rows in the database cache store) by fuzzing the
        // query string (ТЗ §13.1). Only real, resolvable filters ever reach the cache key or query.
        $validRegionIds = Region::query()->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $filters = array_filter([
            'type' => in_array(request('type'), IncidentType::values(), true) ? (string) request('type') : null,
            'level' => in_array(request('level'), HazardLevel::values(), true) ? (string) request('level') : null,
            'region' => in_array((string) request('region'), $validRegionIds, true) ? (string) request('region') : null,
            'period' => in_array(request('period'), ['today', 'week', 'month'], true) ? (string) request('period') : null,
        ], fn (?string $value): bool => $value !== null);

        $page = max(1, min((int) request('page', 1), 1000));
        $highLoad = SystemLoadService::isHighLoad();
        $filterKey = md5(json_encode($filters + ['hl' => $highLoad]));

        $incidentsCacheKey = 'incidents.archive.'.$locale.'.page.'.$page.'.'.$filterKey.'.'.$cacheKeyVersion;

        $incidents = Cache::remember($incidentsCacheKey, 3600, function () use ($locale, $filters, $highLoad) {
            $query = Incident::query()->with(['translations', 'region.translations']);

            if ($highLoad) {
                $query->active();
            }

            if (! empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (! empty($filters['level'])) {
                $query->where('hazard_level', $filters['level']);
            }
            if (! empty($filters['region'])) {
                $query->where('region_id', $filters['region']);
            }
            if (! empty($filters['period'])) {
                $period = $filters['period'];
                if ($period === 'today') {
                    $query->whereDate('occurred_at', today());
                } elseif ($period === 'week') {
                    $query->where('occurred_at', '>=', now()->subWeek());
                } elseif ($period === 'month') {
                    $query->where('occurred_at', '>=', now()->subMonth());
                }
            }

            return $query->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'controlled' THEN 1 ELSE 2 END")
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
            'filters' => $filters,
            'types' => collect(IncidentType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])->all(),
            'levels' => collect(HazardLevel::cases())->map(fn ($l) => ['value' => $l->value, 'label' => $l->label()])->all(),
            'regions' => Region::with('translations')->get()->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->translation($locale)?->name])->all(),
        ]);
    }
}
