<?php

namespace App\Cms\Blueprint;

/**
 * A logical group of fields (main column or sidebar).
 */
readonly class BlueprintSection
{
    /**
     * @param  list<BlueprintField>  $fields
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $handle,
        public array $fields,
        public array $config = [],
    ) {}

    public function display(): string
    {
        return (string) ($this->config['display'] ?? str($this->handle)->headline()->toString());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'handle' => $this->handle,
            'display' => $this->display(),
            'fields' => array_map(fn (BlueprintField $field): array => $field->toArray(), $this->fields),
        ];
    }
}
