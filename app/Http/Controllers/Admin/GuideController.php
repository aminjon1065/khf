<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuideAudience;
use App\Enums\IncidentType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGuideRequest;
use App\Http\Requests\Admin\UpdateGuideRequest;
use App\Models\Guide;
use App\Services\Admin\ContentEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
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
        return $this->redirectToContentBrowser('guide');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('guide');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreGuideRequest $request): RedirectResponse
    {
        $guide = $this->entries->store('guide', $request->validated());
        $this->syncFiles($request, $guide);
        $this->flashContentSaved(__('Guide created.'));

        return $this->toContentBrowser('guide');
    }

    public function edit(Guide $guide): Response
    {
        $guide->loadMissing('media');

        return Inertia::render('admin/content/form', $this->formData($guide));
    }

    public function update(UpdateGuideRequest $request, Guide $guide): RedirectResponse
    {
        $this->entries->update('guide', $guide, $request->validated());
        $this->syncFiles($request, $guide);
        $this->flashContentSaved(__('Guide updated.'));

        return $this->toContentBrowser('guide');
    }

    public function destroy(Guide $guide): RedirectResponse
    {
        return $this->moveToTrash($guide, 'admin.content.index', __('Guide moved to trash.'), 'guide');
    }

    public function restore(Guide $guide): RedirectResponse
    {
        return $this->restoreFromTrash($guide, 'admin.content.index', __('Guide restored.'), ['type' => 'guide', 'trashed' => 1]);
    }

    public function forceDelete(Guide $guide): RedirectResponse
    {
        return $this->permanentlyDelete($guide, 'admin.content.index', __('Guide permanently deleted.'), ['type' => 'guide', 'trashed' => 1]);
    }

    private function syncFiles(Request $request, Guide $guide): void
    {
        foreach ($request->file('files') ?? [] as $file) {
            $guide->addMedia($file)->toMediaCollection(Guide::FILES_COLLECTION);
        }

        $removeIds = array_map('intval', $request->input('remove_files', []) ?? []);

        if ($removeIds !== []) {
            $guide->getMedia(Guide::FILES_COLLECTION)
                ->whereIn('id', $removeIds)
                ->each->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Guide $guide): array
    {
        $entry = null;
        $files = [];

        if ($guide) {
            $entry = $this->entries->entryArray($guide, 'guide');
            $entry['hazard_type'] = $entry['hazard_type'] ?? '';

            $files = $guide->getMedia(Guide::FILES_COLLECTION)
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'size' => $media->humanReadableSize,
                    'url' => route('guides.download', [
                        'locale' => app()->getLocale(),
                        'guide' => $guide->id,
                        'media' => $media->id,
                    ]),
                ])
                ->all();
        }

        return $this->contentEntryFormProps(
            'guide',
            $entry,
            [
                'hazard_type' => array_merge(
                    [['value' => '', 'label' => 'Без привязки']],
                    array_map(
                        fn (IncidentType $type) => ['value' => $type->value, 'label' => $type->label()],
                        IncidentType::cases(),
                    ),
                ),
                'audience' => GuideAudience::options(),
            ],
            [
                'existingFiles' => $files,
            ],
        );
    }
}
