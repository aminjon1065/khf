<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ListsTranslatableContent;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIncidentRequest;
use App\Http\Requests\Admin\UpdateIncidentRequest;
use App\Models\Incident;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    use BuildsCmsFormData;
    use ListsTranslatableContent;
    use ManagesSoftDeletableContent;
    use SavesContentRevisions;

    /** @var list<string> */
    private const SORTABLE = ['status', 'hazard_level', 'type', 'occurred_at', 'created_at'];

    public function index(Request $request): Response
    {
        $filters = $this->listFilters($request, 'occurred_at', 'desc');

        $incidents = $this->paginateTranslatable(
            Incident::query()->with(['translations', 'region.translations']),
            $request,
            self::SORTABLE,
            'occurred_at',
            'desc',
            fn (Incident $incident, string $locale) => $this->toRow($incident, $locale),
        );

        return Inertia::render('admin/incidents/index', [
            'incidents' => $incidents,
            'filters' => $filters,
            'trashedCount' => Incident::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $incidents = $this->paginateTrashed(
            Incident::onlyTrashed()->with(['translations', 'region.translations']),
            fn (Incident $incident, string $locale) => $this->toRow($incident, $locale),
        );

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
        $this->saveContentRevision($incident);
        $this->flashContentSaved(__('Incident created.'));

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
        $this->saveContentRevision($incident);
        $this->flashContentSaved(__('Incident updated.'));

        return to_route('admin.incidents.index');
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        return $this->moveToTrash($incident, 'admin.incidents.index', __('Incident moved to trash.'));
    }

    public function restore(Incident $incident): RedirectResponse
    {
        return $this->restoreFromTrash($incident, 'admin.incidents.trash', __('Incident restored.'));
    }

    public function forceDelete(Incident $incident): RedirectResponse
    {
        return $this->permanentlyDelete($incident, 'admin.incidents.trash', __('Incident permanently deleted.'));
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
                ->map(fn (Region $region) => [
                    'id' => $region->id,
                    'name' => $region->translation($locale)?->name ?? $region->code,
                    'lat' => (float) $region->latitude,
                    'lng' => (float) $region->longitude,
                ])
                ->all(),
            'locales' => $this->localeOptions(),
            'defaultLocale' => $this->publicationFormMeta()['defaultLocale'],
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
