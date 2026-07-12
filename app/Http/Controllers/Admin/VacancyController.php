<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmploymentType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVacancyRequest;
use App\Http\Requests\Admin\UpdateVacancyRequest;
use App\Models\Vacancy;
use App\Support\HtmlSanitizer;
use App\Support\PublicationScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VacancyController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('vacancy');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('vacancy');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreVacancyRequest $request): RedirectResponse
    {
        $data = PublicationScheduler::normalize($request->validated());

        $vacancy = Vacancy::create([
            'employment_type' => $data['employment_type'],
            'status' => $data['status'],
            'positions_count' => $data['positions_count'],
            'published_at' => $data['published_at'] ?? null,
            'unpublished_at' => $data['unpublished_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        $vacancy->upsertTranslations($this->translationsPayload($data));
        $this->flashContentSaved(__('Vacancy created.'));

        return $this->toContentBrowser('vacancy');
    }

    public function edit(Vacancy $vacancy): Response
    {
        $vacancy->load('translations');

        return Inertia::render('admin/content/form', $this->formData($vacancy));
    }

    public function update(UpdateVacancyRequest $request, Vacancy $vacancy): RedirectResponse
    {
        $data = PublicationScheduler::normalize($request->validated());

        $vacancy->update([
            'employment_type' => $data['employment_type'],
            'status' => $data['status'],
            'positions_count' => $data['positions_count'],
            'published_at' => $data['published_at'] ?? null,
            'unpublished_at' => $data['unpublished_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
        ]);
        $vacancy->upsertTranslations($this->translationsPayload($data));
        $this->flashContentSaved(__('Vacancy updated.'));

        return $this->toContentBrowser('vacancy');
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        return $this->moveToTrash($vacancy, 'admin.content.index', __('Vacancy moved to trash.'), 'vacancy');
    }

    public function restore(Vacancy $vacancy): RedirectResponse
    {
        return $this->restoreFromTrash($vacancy, 'admin.content.index', __('Vacancy restored.'), ['type' => 'vacancy', 'trashed' => 1]);
    }

    public function forceDelete(Vacancy $vacancy): RedirectResponse
    {
        return $this->permanentlyDelete($vacancy, 'admin.content.index', __('Vacancy permanently deleted.'), ['type' => 'vacancy', 'trashed' => 1]);
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

        return $this->contentEntryFormProps(
            'vacancy',
            $vacancy ? [
                'id' => $vacancy->id,
                'employment_type' => $vacancy->employment_type->value,
                'status' => $vacancy->status->value,
                'positions_count' => $vacancy->positions_count,
                'published_at' => $vacancy->published_at?->format('Y-m-d\TH:i'),
                'unpublished_at' => $vacancy->unpublished_at?->format('Y-m-d\TH:i'),
                'deadline_at' => $vacancy->deadline_at?->format('Y-m-d'),
                'translations' => $translations,
            ] : null,
            [
                'employment_type' => EmploymentType::options(),
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
