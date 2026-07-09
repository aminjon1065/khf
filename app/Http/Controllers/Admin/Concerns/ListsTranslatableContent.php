<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginated listing with search and sort for translatable CMS content.
 */
trait ListsTranslatableContent
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $sortable
     * @param  callable(TModel, string): array<string, mixed>  $toRow
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    protected function paginateTranslatable(
        Builder $query,
        Request $request,
        array $sortable,
        string $defaultSort,
        string $defaultDirection,
        callable $toRow,
        string $searchColumn = 'title',
    ): LengthAwarePaginator {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), $sortable, true)
            ? (string) $request->string('sort')
            : $defaultSort;

        $direction = (string) $request->string('direction');
        if ($direction !== 'desc' && $direction !== 'asc') {
            $direction = $defaultDirection;
        }

        return $query
            ->when($search !== '', fn (Builder $inner) => $inner->whereHas(
                'translations',
                fn (Builder $translationQuery) => $translationQuery->where($searchColumn, 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($model) => $toRow($model, $locale));
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  callable(TModel, string): array<string, mixed>  $toRow
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    protected function paginateTrashed(
        Builder $query,
        callable $toRow,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $locale = app()->getLocale();

        return $query
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->through(fn ($model) => $toRow($model, $locale));
    }

    /**
     * @return array{search: string, sort: string, direction: string}
     */
    protected function listFilters(Request $request, string $defaultSort, string $defaultDirection): array
    {
        $sort = (string) $request->string('sort');
        $direction = (string) $request->string('direction');

        if ($direction !== 'desc' && $direction !== 'asc') {
            $direction = $defaultDirection;
        }

        return [
            'search' => trim((string) $request->string('search')),
            'sort' => $sort !== '' ? $sort : $defaultSort,
            'direction' => $direction,
        ];
    }
}
