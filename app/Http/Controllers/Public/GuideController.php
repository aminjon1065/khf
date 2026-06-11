<?php

namespace App\Http\Controllers\Public;

use App\Enums\GuideAudience;
use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideTranslation;
use App\Support\LocaleUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuideController extends Controller
{
    /**
     * Public safety-guides catalogue (ТЗ §6.5), filterable by audience (general / children — the
     * educational sub-section). Each guide is shown with its hazard type for quick scanning.
     */
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $audience = in_array((string) $request->string('audience'), GuideAudience::values(), true)
            ? (string) $request->string('audience')
            : null;

        $guides = Guide::published()
            ->with(['translations', 'media'])
            ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
            ->when($audience !== null, fn (Builder $query) => $query->where('audience', $audience))
            ->orderBy('sort_order')
            ->get()
            ->map(function (Guide $guide) use ($locale): array {
                $translation = $guide->translation($locale);

                return [
                    'title' => $translation?->title,
                    'slug' => $translation?->slug,
                    'summary' => $translation?->summary,
                    'hazard_type' => $guide->hazard_type?->value,
                    'hazard_label' => $guide->hazard_type?->label(),
                    'hazard_icon' => $guide->hazard_type?->icon(),
                    'audience' => $guide->audience->value,
                    'files_count' => $guide->getMedia(Guide::FILES_COLLECTION)->count(),
                ];
            })
            ->all();

        return Inertia::render('public/guides/index', [
            'guides' => $guides,
            'filters' => ['audience' => $audience],
            'audiences' => GuideAudience::options(),
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        $appLocale = app()->getLocale();

        $translation = GuideTranslation::query()
            ->where('locale', $appLocale)
            ->where('slug', $slug)
            ->first();

        abort_if($translation === null, 404);

        $guide = Guide::published()
            ->whereKey($translation->guide_id)
            ->with(['media', 'translations'])
            ->first();

        abort_if($guide === null, 404);

        $urls = app(LocaleUrls::class)->contentUrls(
            'guides.show',
            $guide->translations->pluck('slug', 'locale')->all(),
        );

        return Inertia::render('public/guides/show', [
            'guide' => [
                'title' => $translation->title,
                'summary' => $translation->summary,
                'content' => $translation->content,
                'hazard_label' => $guide->hazard_type?->label(),
                'audience_label' => $guide->audience->label(),
                'files' => $guide->getMedia(Guide::FILES_COLLECTION)->map(fn ($media) => [
                    'name' => $media->file_name,
                    'size' => $media->humanReadableSize,
                    'url' => route('guides.download', ['locale' => $appLocale, 'guide' => $guide->id, 'media' => $media->id]),
                ])->all(),
            ],
            'seo' => [
                'title' => $translation->seo_title ?: $translation->title,
                'description' => $translation->seo_description ?: $translation->summary,
            ],
            'localeSwitch' => $urls['switch'],
            'seoAlternates' => $urls['alternates'],
        ]);
    }

    /**
     * Controlled download — files live outside the public webroot (ТЗ §12.4).
     */
    public function download(string $locale, Guide $guide, int $media): BinaryFileResponse
    {
        abort_unless($guide->status->value === 'published', 404);

        $file = $guide->getMedia(Guide::FILES_COLLECTION)->firstWhere('id', $media);

        abort_if($file === null, 404);

        return response()->download($file->getPath(), $file->file_name);
    }
}
