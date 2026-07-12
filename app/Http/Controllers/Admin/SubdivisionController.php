<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubdivisionRequest;
use App\Http\Requests\Admin\UpdateSubdivisionRequest;
use App\Models\Subdivision;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubdivisionController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('subdivision');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreSubdivisionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $subdivision = Subdivision::create([
            'status' => $data['status'],
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'staff_count' => $data['staff_count'] ?? null,
        ]);
        $subdivision->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subdivision created.')]);

        return $this->toContentBrowser('subdivision');
    }

    public function edit(Subdivision $subdivision): Response
    {
        $subdivision->load('translations');

        return Inertia::render('admin/content/form', $this->formData($subdivision));
    }

    public function update(UpdateSubdivisionRequest $request, Subdivision $subdivision): RedirectResponse
    {
        $data = $request->validated();

        $subdivision->update([
            'status' => $data['status'],
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'staff_count' => $data['staff_count'] ?? null,
        ]);
        $subdivision->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subdivision updated.')]);

        return $this->toContentBrowser('subdivision');
    }

    public function destroy(Subdivision $subdivision): RedirectResponse
    {
        $subdivision->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subdivision deleted.')]);

        return $this->toContentBrowser('subdivision');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Subdivision $subdivision): array
    {
        $locale = app()->getLocale();
        $translations = [];

        if ($subdivision) {
            foreach ($subdivision->translations as $translation) {
                $translations[$translation->locale] = [
                    'name' => $translation->name,
                    'head' => $translation->head,
                    'functions' => $translation->functions,
                    'address' => $translation->address,
                ];
            }
        }

        $parents = Subdivision::query()
            ->with('translations')
            ->when($subdivision, fn (Builder $query) => $query->whereKeyNot($subdivision->id))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Subdivision $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->translation($locale)?->name ?? "#{$candidate->id}",
            ])
            ->all();

        return $this->contentEntryFormProps(
            'subdivision',
            $subdivision ? [
                'id' => $subdivision->id,
                'status' => $subdivision->status->value,
                'parent_id' => $subdivision->parent_id,
                'sort_order' => $subdivision->sort_order,
                'email' => $subdivision->email,
                'phone' => $subdivision->phone,
                'staff_count' => $subdivision->staff_count,
                'translations' => $translations,
            ] : null,
            [
                'parent_id' => $parents,
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
            ->filter(fn (array $translation) => filled($translation['name'] ?? null))
            ->map(fn (array $translation) => [
                'name' => $translation['name'],
                'head' => $translation['head'] ?? null,
                'functions' => $this->sanitizer->clean($translation['functions'] ?? null),
                'address' => $translation['address'] ?? null,
            ])
            ->all();
    }
}
