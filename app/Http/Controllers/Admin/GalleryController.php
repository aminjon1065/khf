<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryRequest;
use App\Http\Requests\Admin\UpdateGalleryRequest;
use App\Models\Gallery;
use App\Services\Admin\ContentEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

    public function __construct(private ContentEntryService $entries) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('gallery');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreGalleryRequest $request): RedirectResponse
    {
        $gallery = $this->entries->store('gallery', $request->validated());
        $this->syncPhotos($request, $gallery);

        $this->flashContentSaved(__('Gallery created.'));

        return $this->toContentBrowser('gallery');
    }

    public function edit(Gallery $gallery): Response
    {
        $gallery->loadMissing('media');

        return Inertia::render('admin/content/form', $this->formData($gallery));
    }

    public function update(UpdateGalleryRequest $request, Gallery $gallery): RedirectResponse
    {
        $this->entries->update('gallery', $gallery, $request->validated());
        $this->syncPhotos($request, $gallery);

        $this->flashContentSaved(__('Gallery updated.'));

        return $this->toContentBrowser('gallery');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $this->entries->destroy('gallery', $gallery);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gallery deleted.')]);

        return $this->toContentBrowser('gallery');
    }

    /**
     * Append uploaded photos and remove any flagged for deletion (ТЗ §20 «ш»).
     */
    private function syncPhotos(Request $request, Gallery $gallery): void
    {
        foreach ($request->file('photos') ?? [] as $photo) {
            $gallery->addMedia($photo)->toMediaCollection(Gallery::PHOTOS_COLLECTION);
        }

        $removeIds = array_map('intval', $request->input('remove_photos', []) ?? []);

        if ($removeIds !== []) {
            $gallery->getMedia(Gallery::PHOTOS_COLLECTION)
                ->whereIn('id', $removeIds)
                ->each->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Gallery $gallery): array
    {
        $existingPhotos = [];

        if ($gallery) {
            $existingPhotos = $gallery->getMedia(Gallery::PHOTOS_COLLECTION)
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl('thumb'),
                    'name' => $media->file_name,
                ])
                ->all();
        }

        return $this->contentEntryFormProps(
            'gallery',
            $gallery ? $this->entries->entryArray($gallery, 'gallery') : null,
            [],
            [
                'existingPhotos' => $existingPhotos,
            ],
        );
    }
}
