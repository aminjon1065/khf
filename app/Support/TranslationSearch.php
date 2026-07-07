<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Applies locale-scoped text search on translation queries — MySQL FULLTEXT when available,
 * LIKE fallback for SQLite tests (ТЗ §10).
 */
class TranslationSearch
{
    public function supportsFullText(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    /**
     * @param  list<string>  $columns
     */
    public function apply(Builder $query, string $locale, array $columns, string $term): void
    {
        $likeTerm = '%'.$term.'%';

        $query->where('locale', $locale);

        if ($this->supportsFullText()) {
            $query->whereFullText($columns, $term);

            return;
        }

        $query->where(function (Builder $inner) use ($columns, $likeTerm): void {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', $likeTerm);
            }
        });
    }
}
