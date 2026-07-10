<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceCategory;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGovServiceRequest;
use App\Http\Requests\Admin\UpdateGovServiceRequest;
use App\Models\GovService;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GovServiceController extends Controller
{
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use SavesContentRevisions;

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));

        $services = GovService::query()
            ->with('translations')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (GovService $service) => [
                'id' => $service->id,
                'title' => $service->translation($locale)?->title ?? '—',
                'category' => $service->category->value,
                'category_label' => $service->category->label(),
                'status' => $service->status->value,
                'status_label' => $service->status->label(),
                'is_online' => $service->is_online,
                'locales' => $service->translatedLocales(),
                'sort_order' => $service->sort_order,
            ]);

        return Inertia::render('admin/services/index', [
            'services' => $services,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/services/form', $this->formData(null));
    }

    public function store(StoreGovServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $service = GovService::create([
            'category' => $data['category'],
            'status' => $data['status'],
            'is_online' => (bool) ($data['is_online'] ?? false),
            'external_url' => $data['external_url'] ?? null,
            'processing_time' => $data['processing_time'] ?? null,
            'fee' => $data['fee'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $service->upsertTranslations($this->translationsPayload($data));
        $service->load('translations');
        $this->saveContentRevision($service);

        $this->flashContentSaved(__('Service created.'));

        return to_route('admin.services.index');
    }

    public function edit(GovService $govService): Response
    {
        $govService->load('translations');

        return Inertia::render('admin/services/form', $this->formData($govService));
    }

    public function update(UpdateGovServiceRequest $request, GovService $govService): RedirectResponse
    {
        $data = $request->validated();

        $govService->update([
            'category' => $data['category'],
            'status' => $data['status'],
            'is_online' => (bool) ($data['is_online'] ?? false),
            'external_url' => $data['external_url'] ?? null,
            'processing_time' => $data['processing_time'] ?? null,
            'fee' => $data['fee'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $govService->upsertTranslations($this->translationsPayload($data));
        $govService->load('translations');
        $this->saveContentRevision($govService);

        $this->flashContentSaved(__('Service updated.'));

        return to_route('admin.services.index');
    }

    public function destroy(GovService $govService): RedirectResponse
    {
        $govService->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Service deleted.')]);

        return to_route('admin.services.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?GovService $service): array
    {
        $translations = [];

        if ($service) {
            foreach ($service->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'summary' => $translation->summary,
                    'description' => $translation->description,
                    'eligibility' => $translation->eligibility,
                    'required_documents' => $translation->required_documents,
                    'seo_title' => $translation->seo_title,
                    'seo_description' => $translation->seo_description,
                ];
            }
        }

        return [
            'service' => $service ? [
                'id' => $service->id,
                'category' => $service->category->value,
                'status' => $service->status->value,
                'is_online' => $service->is_online,
                'external_url' => $service->external_url,
                'processing_time' => $service->processing_time,
                'fee' => $service->fee,
                'sort_order' => $service->sort_order,
                'translations' => $translations,
            ] : null,
            ...$this->publicationFormMeta($service?->status),
            ...$this->blueprintFormProps('gov_service'),
            'fieldOptions' => [
                'category' => ServiceCategory::options(),
            ],
            'locales' => $this->localeOptions(),
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
            ->map(fn (array $translation, string $locale) => [
                'title' => $translation['title'],
                'slug' => filled($translation['slug'] ?? null)
                    ? $translation['slug']
                    : Str::tajikSlug($translation['title']).'-'.$locale,
                'summary' => $translation['summary'] ?? null,
                'description' => $this->sanitizer->clean($translation['description'] ?? null),
                'eligibility' => $this->sanitizer->clean($translation['eligibility'] ?? null),
                'required_documents' => $this->sanitizer->clean($translation['required_documents'] ?? null),
                'seo_title' => $translation['seo_title'] ?? null,
                'seo_description' => $translation['seo_description'] ?? null,
            ])
            ->all();
    }
}
