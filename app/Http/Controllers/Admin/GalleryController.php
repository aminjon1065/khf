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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

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
        $data = $request->validated();

        $gallery = Gallery::create([
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $gallery->upsertTranslations($this->translationsPayload($data));
        $this->syncPhotos($request, $gallery);
        $gallery->load(['translations', 'media']);
        $this->saveContentRevision($gallery);

        $this->flashContentSaved(__('Gallery created.'));

        return $this->toContentBrowser('gallery');
    }

    public function edit(Gallery $gallery): Response
    {
        $gallery->load(['translations', 'media']);

        return Inertia::render('admin/content/form', $this->formData($gallery));
    }

    public function update(UpdateGalleryRequest $request, Gallery $gallery): RedirectResponse
    {
        $data = $request->validated();

        $gallery->update([
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $gallery->upsertTranslations($this->translationsPayload($data));
        $this->syncPhotos($request, $gallery);
        $gallery->load(['translations', 'media']);
        $this->saveContentRevision($gallery);

        $this->flashContentSaved(__('Gallery updated.'));

        return $this->toContentBrowser('gallery');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $gallery->delete();

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
        $translations = [];
        $existingPhotos = [];

        if ($gallery) {
            foreach ($gallery->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'description' => $translation->description,
                ];
            }

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
            $gallery ? [
                'id' => $gallery->id,
                'status' => $gallery->status->value,
                'sort_order' => $gallery->sort_order,
                'translations' => $translations,
            ] : null,
            [],
            [
                'existingPhotos' => $existingPhotos,
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
                'slug' => $translation['slug'] ?? Str::tajikSlug($translation['title']),
                'description' => $translation['description'] ?? null,
            ])
            ->all();
    }
}
