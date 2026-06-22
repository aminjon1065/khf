<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = (string) $request->string('sort') === 'created_at' ? 'created_at' : 'sort_order';
        $direction = (string) $request->string('direction') === 'desc' ? 'desc' : 'asc';

        $categories = Category::query()
            ->with('translations')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('name', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->translation($locale)?->name ?? '—',
                'locales' => $category->translatedLocales(),
                'sort_order' => $category->sort_order,
            ]);

        return Inertia::render('admin/categories/index', [
            'categories' => $categories,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/categories/form', $this->formData(null));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $category = Category::create(['sort_order' => $data['sort_order'] ?? 0]);
        $category->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return to_route('admin.categories.index');
    }

    public function edit(Category $category): Response
    {
        $category->load('translations');

        return Inertia::render('admin/categories/form', $this->formData($category));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        $category->update(['sort_order' => $data['sort_order'] ?? 0]);
        $category->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return to_route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return to_route('admin.categories.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Category $category): array
    {
        $translations = [];

        if ($category) {
            foreach ($category->translations as $translation) {
                $translations[$translation->locale] = [
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                ];
            }
        }

        return [
            'category' => $category ? [
                'id' => $category->id,
                'sort_order' => $category->sort_order,
                'translations' => $translations,
            ] : null,
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
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
            ->filter(fn (array $translation) => filled($translation['name'] ?? null))
            ->map(fn (array $translation) => [
                'name' => $translation['name'],
                'slug' => $translation['slug'] ?? Str::tajikSlug($translation['name']),
            ])
            ->all();
    }
}
