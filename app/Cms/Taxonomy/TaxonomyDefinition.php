<?php

namespace App\Cms\Taxonomy;

readonly class TaxonomyDefinition
{
    /**
     * @param  list<string>  $collections
     */
    public function __construct(
        public string $handle,
        public string $label,
        public string $modelClass,
        public string $cardinality,
        public string $field,
        public array $collections,
    ) {}

    public function isMultiple(): bool
    {
        return $this->cardinality === 'multiple';
    }
}
