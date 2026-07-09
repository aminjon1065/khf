<?php

namespace App\Services\Cms;

use App\Cms\Taxonomy\TaxonomyDefinition;
use App\Cms\Taxonomy\TaxonomyRegistry;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;

class TaxonomyService
{
    public function __construct(private TaxonomyRegistry $registry) {}

    /**
     * @return list<array{handle: string, label: string, cardinality: string, field: string, collections: list<string>}>
     */
    public function catalog(): array
    {
        return array_map(
            fn (TaxonomyDefinition $definition): array => [
                'handle' => $definition->handle,
                'label' => $definition->label,
                'cardinality' => $definition->cardinality,
                'field' => $definition->field,
                'collections' => $definition->collections,
            ],
            $this->registry->all(),
        );
    }

    /**
     * @return list<array{id: int, name: string, slug: string|null}>
     */
    public function items(string $handle, ?string $locale = null): array
    {
        $definition = $this->registry->get($handle);
        $locale ??= app()->getLocale();

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition->modelClass;

        return $modelClass::query()
            ->with('translations')
            ->when(
                $definition->modelClass === Category::class,
                fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                fn ($query) => $query->orderBy('id'),
            )
            ->get()
            ->map(fn (Model $item): array => [
                'id' => $item->getKey(),
                'name' => method_exists($item, 'translation')
                    ? ($item->translation($locale)?->name ?? $item->translation()?->name ?? "#{$item->getKey()}")
                    : (string) $item->getKey(),
                'slug' => method_exists($item, 'translation')
                    ? $item->translation($locale)?->slug
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<array{id: int, name: string}>>
     */
    public function fieldOptionsForCollection(string $collection, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $options = [];

        foreach ($this->registry->forCollection($collection) as $definition) {
            $options[$definition->field] = collect($this->items($definition->handle, $locale))
                ->map(fn (array $item): array => ['id' => $item['id'], 'name' => $item['name']])
                ->all();
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function syncForModel(Model $model, array $data): void
    {
        $collection = match ($model::class) {
            Post::class => 'post',
            Page::class => 'page',
            default => null,
        };

        if ($collection === null) {
            return;
        }

        foreach ($this->registry->forCollection($collection) as $definition) {
            if (! array_key_exists($definition->field, $data)) {
                continue;
            }

            if ($definition->isMultiple() && method_exists($model, 'tags')) {
                $model->tags()->sync($data[$definition->field] ?? []);

                continue;
            }

            if ($definition->field === 'category_id' && $model instanceof Post) {
                $model->update(['category_id' => $data['category_id'] ?? null]);
            }
        }
    }

    /**
     * @return list<array{id: int, name: string, slug: string|null}>
     */
    public function termsForModel(Model $model, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        if (! method_exists($model, 'tags')) {
            return [];
        }

        $model->loadMissing('tags.translations');

        return $model->tags
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->translation($locale)?->name ?? "#{$tag->id}",
                'slug' => $tag->translation($locale)?->slug,
            ])
            ->values()
            ->all();
    }
}
