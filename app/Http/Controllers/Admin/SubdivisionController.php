<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubdivisionRequest;
use App\Http\Requests\Admin\UpdateSubdivisionRequest;
use App\Models\Subdivision;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubdivisionController extends Controller
{
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));

        $subdivisions = Subdivision::query()
            ->with(['translations', 'parent.translations'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('name', 'like', "%{$search}%"),
            ))
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Subdivision $subdivision) => [
                'id' => $subdivision->id,
                'name' => $subdivision->translation($locale)?->name ?? '—',
                'parent' => $subdivision->parent?->translation($locale)?->name,
                'head' => $subdivision->translation($locale)?->head,
                'status' => $subdivision->status->value,
                'status_label' => $subdivision->status->label(),
                'staff_count' => $subdivision->staff_count,
                'locales' => $subdivision->translatedLocales(),
                'sort_order' => $subdivision->sort_order,
            ]);

        return Inertia::render('admin/structure/index', [
            'subdivisions' => $subdivisions,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/structure/form', $this->formData(null));
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

        return to_route('admin.structure.index');
    }

    public function edit(Subdivision $subdivision): Response
    {
        $subdivision->load('translations');

        return Inertia::render('admin/structure/form', $this->formData($subdivision));
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

        return to_route('admin.structure.index');
    }

    public function destroy(Subdivision $subdivision): RedirectResponse
    {
        $subdivision->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subdivision deleted.')]);

        return to_route('admin.structure.index');
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

        return [
            'subdivision' => $subdivision ? [
                'id' => $subdivision->id,
                'status' => $subdivision->status->value,
                'parent_id' => $subdivision->parent_id,
                'sort_order' => $subdivision->sort_order,
                'email' => $subdivision->email,
                'phone' => $subdivision->phone,
                'staff_count' => $subdivision->staff_count,
                'translations' => $translations,
            ] : null,
            ...$this->publicationFormMeta($subdivision?->status),
            ...$this->blueprintFormProps('subdivision'),
            'fieldOptions' => [
                'parent_id' => $parents,
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
