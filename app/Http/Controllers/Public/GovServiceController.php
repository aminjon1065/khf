<?php

namespace App\Http\Controllers\Public;

use App\Enums\ServiceCategory;
use App\Http\Controllers\Controller;
use App\Models\GovService;
use App\Models\GovServiceTranslation;
use App\Support\LocaleUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GovServiceController extends Controller
{
    /**
     * Government services catalogue (ТЗ §20 «ф»).
     */
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $category = in_array((string) $request->string('category'), ServiceCategory::values(), true)
            ? (string) $request->string('category')
            : null;

        $services = GovService::published()
            ->with('translations')
            ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
            ->when($category !== null, fn (Builder $query) => $query->where('category', $category))
            ->orderBy('sort_order')
            ->get()
            ->map(function (GovService $service) use ($locale): array {
                $translation = $service->translation($locale);

                return [
                    'title' => $translation?->title,
                    'slug' => $translation?->slug,
                    'summary' => $translation?->summary,
                    'category' => $service->category->value,
                    'category_label' => $service->category->label(),
                    'is_online' => $service->is_online,
                    'processing_time' => $service->processing_time,
                    'fee' => $service->fee,
                ];
            })
            ->all();

        return Inertia::render('public/services/index', [
            'services' => $services,
            'filters' => ['category' => $category],
            'categories' => ServiceCategory::options(),
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        $appLocale = app()->getLocale();

        $translation = GovServiceTranslation::query()
            ->where('slug', $slug)
            ->first();

        abort_if($translation === null, 404);

        $service = GovService::published()
            ->whereKey($translation->gov_service_id)
            ->with('translations')
            ->first();

        abort_if($service === null, 404);

        $resolved = $service->translation($appLocale);

        abort_if($resolved === null, 404);

        $urls = app(LocaleUrls::class)->contentUrls(
            'services.show',
            $service->translations->pluck('slug', 'locale')->all(),
        );

        return Inertia::render('public/services/show', [
            'service' => [
                'title' => $resolved->title,
                'summary' => $resolved->summary,
                'description' => $resolved->description,
                'eligibility' => $resolved->eligibility,
                'required_documents' => $resolved->required_documents,
                'category_label' => $service->category->label(),
                'is_online' => $service->is_online,
                'external_url' => $service->external_url,
                'processing_time' => $service->processing_time,
                'fee' => $service->fee,
            ],
            'seo' => [
                'title' => $resolved->seo_title ?: $resolved->title,
                'description' => $resolved->seo_description ?: $resolved->summary,
            ],
            'localeSwitch' => $urls['switch'],
            'seoAlternates' => $urls['alternates'],
        ]);
    }
}
