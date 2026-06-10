<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIncidentRequest;
use App\Http\Requests\Admin\UpdateIncidentRequest;
use App\Models\Incident;
use App\Models\Language;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['status', 'hazard_level', 'type', 'occurred_at', 'created_at'];

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'occurred_at';
        $direction = (string) $request->string('direction') === 'asc' ? 'asc' : 'desc';

        $incidents = Incident::query()
            ->with(['translations', 'region.translations'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Incident $incident) => $this->toRow($incident, $locale));

        return Inertia::render('admin/incidents/index', [
            'incidents' => $incidents,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'trashedCount' => Incident::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $locale = app()->getLocale();

        $incidents = Incident::onlyTrashed()
            ->with(['translations', 'region.translations'])
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn (Incident $incident) => $this->toRow($incident, $locale));

        return Inertia::render('admin/incidents/trash', ['incidents' => $incidents]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/incidents/form', $this->formData(null));
    }

    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $incident = Incident::create([
            'type' => $data['type'],
            'hazard_level' => $data['hazard_level'],
            'status' => $data['status'],
            'region_id' => $data['region_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? null,
        ]);
        $incident->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident created.')]);

        return to_route('admin.incidents.index');
    }

    public function edit(Incident $incident): Response
    {
        $incident->load('translations');

        return Inertia::render('admin/incidents/form', $this->formData($incident));
    }

    public function update(UpdateIncidentRequest $request, Incident $incident): RedirectResponse
    {
        $data = $request->validated();

        $incident->update([
            'type' => $data['type'],
            'hazard_level' => $data['hazard_level'],
            'status' => $data['status'],
            'region_id' => $data['region_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? null,
        ]);
        $incident->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident updated.')]);

        return to_route('admin.incidents.index');
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        $incident->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident moved to trash.')]);

        return to_route('admin.incidents.index');
    }

    public function restore(Incident $incident): RedirectResponse
    {
        $incident->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident restored.')]);

        return to_route('admin.incidents.trash');
    }

    public function forceDelete(Incident $incident): RedirectResponse
    {
        $incident->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident permanently deleted.')]);

        return to_route('admin.incidents.trash');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Incident $incident, string $locale): array
    {
        return [
            'id' => $incident->id,
            'title' => $incident->translation($locale)?->title ?? '—',
            'locales' => $incident->translatedLocales(),
            'type' => $incident->type->value,
            'type_label' => $incident->type->label(),
            'hazard_level' => $incident->hazard_level->value,
            'hazard_label' => $incident->hazard_level->label(),
            'hazard_color' => $incident->hazard_level->color(),
            'status' => $incident->status->value,
            'status_label' => $incident->status->label(),
            'region' => $incident->region?->translation($locale)?->name,
            'occurred_at' => $incident->occurred_at?->format('d.m.Y H:i'),
            'deleted_at' => $incident->deleted_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Incident $incident): array
    {
        $locale = app()->getLocale();
        $translations = [];

        if ($incident) {
            foreach ($incident->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'description' => $translation->description,
                ];
            }
        }

        return [
            'incident' => $incident ? [
                'id' => $incident->id,
                'type' => $incident->type->value,
                'hazard_level' => $incident->hazard_level->value,
                'status' => $incident->status->value,
                'region_id' => $incident->region_id,
                'latitude' => $incident->latitude,
                'longitude' => $incident->longitude,
                'occurred_at' => $incident->occurred_at?->format('Y-m-d\TH:i'),
                'translations' => $translations,
            ] : null,
            'types' => IncidentType::options(),
            'levels' => HazardLevel::options(),
            'statuses' => IncidentStatus::options(),
            'regions' => Region::query()
                ->with('translations')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Region $region) => ['id' => $region->id, 'name' => $region->translation($locale)?->name ?? $region->code])
                ->all(),
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'defaultLocale' => Language::defaultCode(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data): array
    {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation['title'] ?? null))
            ->map(fn (array $translation) => [
                'title' => $translation['title'],
                'description' => $translation['description'] ?? null,
            ])
            ->all();
    }
}
