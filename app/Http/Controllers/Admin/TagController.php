<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = (string) $request->string('sort') === 'created_at' ? 'created_at' : 'id';
        $direction = (string) $request->string('direction') === 'desc' ? 'desc' : 'asc';

        $tags = Tag::query()
            ->with('translations')
            ->withCount(['posts', 'documents'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('name', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->translation($locale)?->name ?? '—',
                'locales' => $tag->translatedLocales(),
                'posts_count' => $tag->posts_count,
                'documents_count' => $tag->documents_count,
            ]);

        return Inertia::render('admin/tags/index', [
            'tags' => $tags,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tags/form', $this->formData(null));
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $tag = Tag::create();
        $tag->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag created.')]);

        return to_route('admin.tags.index');
    }

    public function edit(Tag $tag): Response
    {
        $tag->load('translations');

        return Inertia::render('admin/tags/form', $this->formData($tag));
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $data = $request->validated();

        $tag->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag updated.')]);

        return to_route('admin.tags.index');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag deleted.')]);

        return to_route('admin.tags.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Tag $tag): array
    {
        $translations = [];

        if ($tag) {
            foreach ($tag->translations as $translation) {
                $translations[$translation->locale] = [
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                ];
            }
        }

        return [
            'tag' => $tag ? [
                'id' => $tag->id,
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
