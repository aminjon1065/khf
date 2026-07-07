<?php

namespace App\Support;

/**
 * Wraps query-term matches in <mark> for search results (ТЗ §6.10). Input is escaped first.
 */
class SearchHighlighter
{
    public function highlight(?string $text, string $query): ?string
    {
        if ($text === null || trim($text) === '' || trim($query) === '') {
            return $text;
        }

        $escaped = e($text);
        $terms = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            if (mb_strlen($term) < 2) {
                continue;
            }

            $pattern = '/('.preg_quote($term, '/').')/iu';
            $escaped = preg_replace(
                $pattern,
                '<mark class="rounded bg-yellow-200/80 px-0.5 dark:bg-yellow-500/30">$1</mark>',
                $escaped,
            ) ?? $escaped;
        }

        return $escaped;
    }
}
