<?php

namespace App\Cms\Taxonomy;

use InvalidArgumentException;

class TaxonomyRegistry
{
    /** @var array<string, TaxonomyDefinition> */
    private array $definitions = [];

    public function __construct()
    {
        foreach (config('cms.taxonomies', []) as $handle => $definition) {
            $this->definitions[$handle] = new TaxonomyDefinition(
                handle: (string) $handle,
                label: (string) ($definition['label'] ?? $handle),
                modelClass: (string) $definition['model'],
                cardinality: (string) ($definition['cardinality'] ?? 'multiple'),
                field: (string) ($definition['field'] ?? "{$handle}_ids"),
                collections: (array) ($definition['collections'] ?? []),
            );
        }
    }

    /**
     * @return list<TaxonomyDefinition>
     */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    public function get(string $handle): TaxonomyDefinition
    {
        if (! isset($this->definitions[$handle])) {
            throw new InvalidArgumentException("Unknown taxonomy [{$handle}].");
        }

        return $this->definitions[$handle];
    }

    public function has(string $handle): bool
    {
        return isset($this->definitions[$handle]);
    }

    /**
     * @return list<TaxonomyDefinition>
     */
    public function forCollection(string $collection): array
    {
        return array_values(array_filter(
            $this->definitions,
            fn (TaxonomyDefinition $definition): bool => in_array($collection, $definition->collections, true),
        ));
    }
}
