<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenderType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenderRequest;
use App\Http\Requests\Admin\UpdateTenderRequest;
use App\Models\Tender;
use App\Support\HtmlSanitizer;
use App\Support\PublicationScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenderController extends Controller
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
        return $this->redirectToContentBrowser('tender');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('tender');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreTenderRequest $request): RedirectResponse
    {
        $data = PublicationScheduler::normalize($request->validated());

        $tender = Tender::create([
            'tender_number' => $data['tender_number'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'budget' => $data['budget'] ?? null,
            'lots_count' => $data['lots_count'],
            'published_at' => $data['published_at'] ?? null,
            'unpublished_at' => $data['unpublished_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        $tender->upsertTranslations($this->translationsPayload($data));
        $this->flashContentSaved(__('Tender created.'));

        return $this->toContentBrowser('tender');
    }

    public function edit(Tender $tender): Response
    {
        $tender->load('translations');

        return Inertia::render('admin/content/form', $this->formData($tender));
    }

    public function update(UpdateTenderRequest $request, Tender $tender): RedirectResponse
    {
        $data = PublicationScheduler::normalize($request->validated());

        $tender->update([
            'tender_number' => $data['tender_number'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'budget' => $data['budget'] ?? null,
            'lots_count' => $data['lots_count'],
            'published_at' => $data['published_at'] ?? null,
            'unpublished_at' => $data['unpublished_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
        ]);
        $tender->upsertTranslations($this->translationsPayload($data));
        $this->flashContentSaved(__('Tender updated.'));

        return $this->toContentBrowser('tender');
    }

    public function destroy(Tender $tender): RedirectResponse
    {
        return $this->moveToTrash($tender, 'admin.content.index', __('Tender moved to trash.'), 'tender');
    }

    public function restore(Tender $tender): RedirectResponse
    {
        return $this->restoreFromTrash($tender, 'admin.content.index', __('Tender restored.'), ['type' => 'tender', 'trashed' => 1]);
    }

    public function forceDelete(Tender $tender): RedirectResponse
    {
        return $this->permanentlyDelete($tender, 'admin.content.index', __('Tender permanently deleted.'), ['type' => 'tender', 'trashed' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Tender $tender): array
    {
        $translations = [];

        if ($tender) {
            foreach ($tender->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'organizer' => $translation->organizer,
                    'summary' => $translation->summary,
                    'description' => $translation->description,
                    'requirements' => $translation->requirements,
                    'terms' => $translation->terms,
                    'seo_title' => $translation->seo_title,
                    'seo_description' => $translation->seo_description,
                ];
            }
        }

        return $this->contentEntryFormProps(
            'tender',
            $tender ? [
                'id' => $tender->id,
                'tender_number' => $tender->tender_number,
                'type' => $tender->type->value,
                'status' => $tender->status->value,
                'budget' => $tender->budget,
                'lots_count' => $tender->lots_count,
                'published_at' => $tender->published_at?->format('Y-m-d\TH:i'),
                'unpublished_at' => $tender->unpublished_at?->format('Y-m-d\TH:i'),
                'deadline_at' => $tender->deadline_at?->format('Y-m-d'),
                'translations' => $translations,
            ] : null,
            [
                'type' => TenderType::options(),
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
                'organizer' => $translation['organizer'] ?? null,
                'summary' => $translation['summary'] ?? null,
                'description' => $this->sanitizer->clean($translation['description'] ?? null),
                'requirements' => $this->sanitizer->clean($translation['requirements'] ?? null),
                'terms' => $this->sanitizer->clean($translation['terms'] ?? null),
                'seo_title' => $translation['seo_title'] ?? null,
                'seo_description' => $translation['seo_description'] ?? null,
            ])
            ->all();
    }
}
