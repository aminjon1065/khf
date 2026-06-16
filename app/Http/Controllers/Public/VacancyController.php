<?php

namespace App\Http\Controllers\Public;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVacancyApplicationRequest;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use App\Models\VacancyTranslation;
use App\Support\LocaleUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class VacancyController extends Controller
{
    /**
     * Public listing of open civil-service vacancies for the current locale (ТЗ §20 «н»).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();
        $page = request('page', 1);
        $cacheKey = 'vacancies.index.'.$locale.'.page.'.$page.'.'.(Vacancy::max('updated_at') ?? 'empty');

        $vacancies = Cache::remember($cacheKey, 3600, function () use ($locale) {
            return Vacancy::open()
                ->with('translations')
                ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
                ->orderByDesc('published_at')
                ->paginate(12)
                ->through(fn (Vacancy $vacancy) => $this->card($vacancy, $locale));
        });

        return Inertia::render('public/vacancies/index', [
            'vacancies' => $vacancies,
        ]);
    }

    /**
     * A single published vacancy resolved by its per-locale slug, with the online application form
     * (ТЗ §20 «н», §21). Not response-cached — it carries the application form and its flash receipt.
     */
    public function show(string $locale, string $slug): Response
    {
        $translation = VacancyTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        abort_if($translation === null, 404);

        $vacancy = Vacancy::published()
            ->whereKey($translation->vacancy_id)
            ->with('translations')
            ->first();

        abort_if($vacancy === null, 404);

        $urls = app(LocaleUrls::class)->contentUrls(
            'vacancies.show',
            $vacancy->translations->pluck('slug', 'locale')->all(),
        );

        return Inertia::render('public/vacancies/show', [
            'vacancy' => [
                'id' => $vacancy->id,
                'title' => $translation->title,
                'department' => $translation->department,
                'location' => $translation->location,
                'salary' => $translation->salary,
                'summary' => $translation->summary,
                'description' => $translation->description,
                'requirements' => $translation->requirements,
                'responsibilities' => $translation->responsibilities,
                'employment_type_label' => $vacancy->employment_type->label(),
                'positions_count' => $vacancy->positions_count,
                'published_at' => $vacancy->published_at?->format('d.m.Y'),
                'updated_at' => $vacancy->updated_at?->format('d.m.Y'),
                'deadline_at' => $vacancy->deadline_at?->format('d.m.Y'),
                'is_open' => $vacancy->isOpen(),
            ],
            'submittedReference' => session('application_reference'),
            'localeSwitch' => $urls['switch'],
            'seoAlternates' => $urls['alternates'],
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'JobPosting',
                'title' => $translation->title,
                'description' => $translation->summary ?? $translation->title,
                'datePosted' => $vacancy->published_at?->toIso8601String(),
                'validThrough' => $vacancy->deadline_at?->toIso8601String(),
                'employmentType' => $vacancy->employment_type->value,
                'hiringOrganization' => [
                    '@type' => 'GovernmentOrganization',
                    'name' => trans('ui.site.full_name'),
                ],
            ],
        ]);
    }

    /**
     * Accept an online application (questionnaire/CV) for a vacancy and return its tracking
     * reference as the receipt confirmation (ТЗ §21). Rate-limited at the route.
     */
    public function apply(StoreVacancyApplicationRequest $request, string $locale, Vacancy $vacancy): RedirectResponse
    {
        abort_unless($vacancy->status === ContentStatus::Published && $vacancy->isOpen(), 404);

        $data = $request->validated();
        unset($data['website'], $data['resume']);

        $application = $vacancy->applications()->create([
            ...$data,
            'reference' => VacancyApplication::generateReference(),
        ]);
        $application->addMediaFromRequest('resume')->toMediaCollection(VacancyApplication::RESUME_COLLECTION);

        $slug = $vacancy->translation($locale)?->slug ?? $vacancy->translation()?->slug;

        return to_route('vacancies.show', ['locale' => $locale, 'slug' => $slug])
            ->with('application_reference', $application->reference);
    }

    /**
     * Public status tracking of an application by reference number (ТЗ §21).
     */
    public function track(Request $request): Response
    {
        $locale = app()->getLocale();
        $reference = trim((string) $request->string('reference'));
        $result = null;

        if ($reference !== '') {
            $application = VacancyApplication::with('vacancy.translations')
                ->where('reference', $reference)
                ->first();

            $result = $application === null
                ? ['found' => false]
                : [
                    'found' => true,
                    'reference' => $application->reference,
                    'vacancy' => $application->vacancy?->translation($locale)?->title,
                    'status' => $application->status->label(),
                    'created_at' => $application->created_at?->format('d.m.Y'),
                    'updated_at' => $application->updated_at?->format('d.m.Y'),
                ];
        }

        return Inertia::render('public/vacancies/track', [
            'reference' => $reference,
            'result' => $result,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function card(Vacancy $vacancy, string $locale): array
    {
        $translation = $vacancy->translation($locale);

        return [
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'department' => $translation?->department,
            'location' => $translation?->location,
            'summary' => $translation?->summary,
            'employment_type_label' => $vacancy->employment_type->label(),
            'positions_count' => $vacancy->positions_count,
            'published_at' => $vacancy->published_at?->format('d.m.Y'),
            'deadline_at' => $vacancy->deadline_at?->format('d.m.Y'),
        ];
    }
}
