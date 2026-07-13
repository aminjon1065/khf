<?php

namespace App\Services\Public\Search;

use App\Support\TranslationSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Shared translation-scoped search query + map for public content types.
 */
class TranslatableContentSearcher
{
    public function __construct(private TranslationSearch $translationSearch) {}

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $fields
     * @param  callable(Builder<Model>): Builder<Model>  $constrain
     * @param  callable(Model, string): array<string, mixed>  $map
     * @return Collection<int, array<string, mixed>>
     */
    public function search(
        string $modelClass,
        string $locale,
        string $query,
        array $fields,
        int $limit,
        callable $constrain,
        callable $map,
    ): Collection {
        /** @var Builder<Model> $builder */
        $builder = $modelClass::query();
        $builder = $constrain($builder);

        return $builder
            ->whereHas(
                'translations',
                fn ($q) => $this->translationSearch->apply($q, $locale, $fields, $query),
            )
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->take($limit)
            ->get()
            ->map(fn (Model $model): array => $map($model, $locale))
            ->values();
    }
}
