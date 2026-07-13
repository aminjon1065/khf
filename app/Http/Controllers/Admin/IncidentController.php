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
use App\Services\Admin\ContentEntryService;
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

    public function __construct(private ContentEntryService $entries) {}

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
        $this->entries->store('incident', $request->validated());
        $this->flashContentSaved(__('Incident created.'));

        return $this->toContentBrowser('incident');
    }

    public function edit(Incident $incident): Response
    {
        return Inertia::render('admin/content/form', $this->formData($incident));
    }

    public function update(UpdateIncidentRequest $request, Incident $incident): RedirectResponse
    {
        $this->entries->update('incident', $incident, $request->validated());
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

        return $this->contentEntryFormProps(
            'incident',
            $incident ? $this->entries->entryArray($incident, 'incident') : null,
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
}
