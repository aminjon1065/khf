<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIncidentRequest;
use App\Http\Requests\Admin\UpdateIncidentRequest;
use App\Models\Incident;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('incident');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('incident');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
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

        return $this->toContentBrowser('incident');
    }

    public function edit(Incident $incident): Response
    {
        $incident->load('translations');

        return Inertia::render('admin/content/form', $this->formData($incident));
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

        return $this->toContentBrowser('incident');
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        return $this->moveToTrash($incident, 'admin.content.index', __('Incident moved to trash.'), 'incident');
    }

    public function restore(Incident $incident): RedirectResponse
    {
        return $this->restoreFromTrash($incident, 'admin.content.index', __('Incident restored.'), ['type' => 'incident', 'trashed' => 1]);
    }

    public function forceDelete(Incident $incident): RedirectResponse
    {
        return $this->permanentlyDelete($incident, 'admin.content.index', __('Incident permanently deleted.'), ['type' => 'incident', 'trashed' => 1]);
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

        return $this->contentEntryFormProps(
            'incident',
            $incident ? [
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
            [
                'type' => IncidentType::options(),
                'hazard_level' => HazardLevel::options(),
                'status' => IncidentStatus::options(),
                'region_id' => Region::query()
                    ->with('translations')
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Region $region) => [
                        'id' => $region->id,
                        'name' => $region->translation($locale)?->name ?? $region->code,
                    ])
                    ->all(),
            ],
            [
                'regionCoordinates' => Region::query()
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn (Region $region) => [
                        $region->id => [
                            'lat' => (float) $region->latitude,
                            'lng' => (float) $region->longitude,
                        ],
                    ])
                    ->all(),
            ],
        );
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
