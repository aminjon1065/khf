<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\EmploymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVacancyRequest;
use App\Http\Requests\Admin\UpdateVacancyRequest;
use App\Models\Language;
use App\Models\Vacancy;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VacancyController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['status', 'employment_type', 'published_at', 'deadline_at', 'created_at'];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'published_at';
        $direction = (string) $request->string('direction') === 'asc' ? 'asc' : 'desc';

        $vacancies = Vacancy::query()
            ->with('translations')
            ->withCount('applications')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vacancy $vacancy) => $this->toRow($vacancy, $locale));

        return Inertia::render('admin/vacancies/index', [
            'vacancies' => $vacancies,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'trashedCount' => Vacancy::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $locale = app()->getLocale();

        $vacancies = Vacancy::onlyTrashed()
            ->with('translations')
            ->withCount('applications')
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn (Vacancy $vacancy) => $this->toRow($vacancy, $locale));

        return Inertia::render('admin/vacancies/trash', ['vacancies' => $vacancies]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/vacancies/form', $this->formData(null));
    }

    public function store(StoreVacancyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $vacancy = Vacancy::create([
            'employment_type' => $data['employment_type'],
            'status' => $data['status'],
            'positions_count' => $data['positions_count'],
            'published_at' => $data['published_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        $vacancy->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vacancy created.')]);

        return to_route('admin.vacancies.index');
    }

    public function edit(Vacancy $vacancy): Response
    {
        $vacancy->load('translations');

        return Inertia::render('admin/vacancies/form', $this->formData($vacancy));
    }

    public function update(UpdateVacancyRequest $request, Vacancy $vacancy): RedirectResponse
    {
        $data = $request->validated();

        $vacancy->update([
            'employment_type' => $data['employment_type'],
            'status' => $data['status'],
            'positions_count' => $data['positions_count'],
            'published_at' => $data['published_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
        ]);
        $vacancy->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vacancy updated.')]);

        return to_route('admin.vacancies.index');
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vacancy moved to trash.')]);

        return to_route('admin.vacancies.index');
    }

    public function restore(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vacancy restored.')]);

        return to_route('admin.vacancies.trash');
    }

    public function forceDelete(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vacancy permanently deleted.')]);

        return to_route('admin.vacancies.trash');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Vacancy $vacancy, string $locale): array
    {
        return [
            'id' => $vacancy->id,
            'title' => $vacancy->translation($locale)?->title ?? '—',
            'department' => $vacancy->translation($locale)?->department,
            'employment_type' => $vacancy->employment_type->value,
            'employment_type_label' => $vacancy->employment_type->label(),
            'status' => $vacancy->status->value,
            'status_label' => $vacancy->status->label(),
            'positions_count' => $vacancy->positions_count,
            'applications_count' => $vacancy->applications_count,
            'locales' => $vacancy->translatedLocales(),
            'published_at' => $vacancy->published_at?->toDateString(),
            'deadline_at' => $vacancy->deadline_at?->toDateString(),
            'is_open' => $vacancy->isOpen(),
            'deleted_at' => $vacancy->deleted_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Vacancy $vacancy): array
    {
        $translations = [];

        if ($vacancy) {
            foreach ($vacancy->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'department' => $translation->department,
                    'location' => $translation->location,
                    'salary' => $translation->salary,
                    'summary' => $translation->summary,
                    'description' => $translation->description,
                    'requirements' => $translation->requirements,
                    'responsibilities' => $translation->responsibilities,
                    'seo_title' => $translation->seo_title,
                    'seo_description' => $translation->seo_description,
                ];
            }
        }

        return [
            'vacancy' => $vacancy ? [
                'id' => $vacancy->id,
                'employment_type' => $vacancy->employment_type->value,
                'status' => $vacancy->status->value,
                'positions_count' => $vacancy->positions_count,
                'published_at' => $vacancy->published_at?->format('Y-m-d\TH:i'),
                'deadline_at' => $vacancy->deadline_at?->format('Y-m-d'),
                'translations' => $translations,
            ] : null,
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'employmentTypes' => EmploymentType::options(),
            'statuses' => array_map(
                fn (ContentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ContentStatus::cases(),
            ),
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
                'slug' => $translation['slug'] ?? Str::slug($translation['title']),
                'department' => $translation['department'] ?? null,
                'location' => $translation['location'] ?? null,
                'salary' => $translation['salary'] ?? null,
                'summary' => $translation['summary'] ?? null,
                'description' => $this->sanitizer->clean($translation['description'] ?? null),
                'requirements' => $this->sanitizer->clean($translation['requirements'] ?? null),
                'responsibilities' => $this->sanitizer->clean($translation['responsibilities'] ?? null),
                'seo_title' => $translation['seo_title'] ?? null,
                'seo_description' => $translation['seo_description'] ?? null,
            ])
            ->all();
    }
}
