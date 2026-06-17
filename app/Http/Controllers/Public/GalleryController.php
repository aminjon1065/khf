<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\GalleryTranslation;
use App\Support\LocaleUrls;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    /**
     * Public list of photo galleries for the current locale (ТЗ §20 «ш»).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $galleries = Gallery::published()
            ->with(['translations', 'media'])
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Gallery $gallery) => [
                'title' => $gallery->translation($locale)?->title,
                'slug' => $gallery->translation($locale)?->slug,
                'description' => $gallery->translation($locale)?->description,
                'cover_url' => $gallery->getFirstMediaUrl(Gallery::PHOTOS_COLLECTION, 'thumb') ?: null,
                'photos_count' => $gallery->getMedia(Gallery::PHOTOS_COLLECTION)->count(),
            ])
            ->all();

        return Inertia::render('public/gallery/index', [
            'galleries' => $galleries,
        ]);
    }

    /**
     * A single published gallery resolved by its per-locale slug (ТЗ §20 «ш»).
     */
    public function show(string $locale, string $slug): Response
    {
        $translation = GalleryTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        abort_if($translation === null, 404);

        $gallery = Gallery::published()
            ->whereKey($translation->gallery_id)
            ->with(['translations', 'media'])
            ->first();

        abort_if($gallery === null, 404);

        $urls = app(LocaleUrls::class)->contentUrls(
            'gallery.show',
            $gallery->translations->pluck('slug', 'locale')->all(),
        );

        return Inertia::render('public/gallery/show', [
            'gallery' => [
                'title' => $translation->title,
                'description' => $translation->description,
                'date' => $gallery->created_at?->format('d.m.Y'),
                'photos' => $gallery->getMedia(Gallery::PHOTOS_COLLECTION)
                    ->map(fn ($media) => [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'thumb' => $media->getUrl('thumb'),
                    ])
                    ->all(),
            ],
            'localeSwitch' => $urls['switch'],
            'seoAlternates' => $urls['alternates'],
        ]);
    }
}
