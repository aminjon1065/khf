<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryRequest;
use App\Http\Requests\Admin\UpdateGalleryRequest;
use App\Models\Gallery;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));

        $galleries = Gallery::query()
            ->with(['translations', 'media'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy('sort_order')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Gallery $gallery) => [
                'id' => $gallery->id,
                'title' => $gallery->translation($locale)?->title ?? '—',
                'status' => $gallery->status->value,
                'status_label' => $gallery->status->label(),
                'photos_count' => $gallery->getMedia(Gallery::PHOTOS_COLLECTION)->count(),
                'cover_url' => $gallery->getFirstMediaUrl(Gallery::PHOTOS_COLLECTION, 'thumb') ?: null,
                'locales' => $gallery->translatedLocales(),
                'sort_order' => $gallery->sort_order,
            ]);

        return Inertia::render('admin/gallery/index', [
            'galleries' => $galleries,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/gallery/form', $this->formData(null));
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gallery created.')]);

        return to_route('admin.gallery.index');
    }

    public function edit(Gallery $gallery): Response
    {
        $gallery->load(['translations', 'media']);

        return Inertia::render('admin/gallery/form', $this->formData($gallery));
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gallery updated.')]);

        return to_route('admin.gallery.index');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $gallery->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gallery deleted.')]);

        return to_route('admin.gallery.index');
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
        $photos = [];

        if ($gallery) {
            foreach ($gallery->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'description' => $translation->description,
                ];
            }

            $photos = $gallery->getMedia(Gallery::PHOTOS_COLLECTION)
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl('thumb'),
                    'name' => $media->file_name,
                ])
                ->all();
        }

        return [
            'gallery' => $gallery ? [
                'id' => $gallery->id,
                'status' => $gallery->status->value,
                'sort_order' => $gallery->sort_order,
                'translations' => $translations,
                'photos' => $photos,
            ] : null,
            'statuses' => array_map(
                fn (ContentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ContentStatus::cases(),
            ),
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
                'slug' => $translation['slug'] ?? Str::tajikSlug($translation['title']),
                'description' => $translation['description'] ?? null,
            ])
            ->all();
    }
}
