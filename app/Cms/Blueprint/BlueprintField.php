<?php

namespace App\Cms\Blueprint;

/**
 * A single field definition inside a blueprint section.
 */
readonly class BlueprintField
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $handle,
        public string $type,
        public array $config = [],
    ) {}

    public function display(): string
    {
        return (string) ($this->config['display'] ?? str($this->handle)->headline()->toString());
    }

    public function isLocalizable(): bool
    {
        return (bool) ($this->config['localizable'] ?? false);
    }

    public function isRequired(): bool
    {
        return (bool) ($this->config['required'] ?? false);
    }

    public function instructions(): ?string
    {
        $instructions = $this->config['instructions'] ?? null;

        return is_string($instructions) && $instructions !== '' ? $instructions : null;
    }

    public function collection(): ?string
    {
        $collection = $this->config['collection'] ?? null;

        return is_string($collection) && $collection !== '' ? $collection : null;
    }

    public function maxItems(): ?int
    {
        return isset($this->config['max']) ? (int) $this->config['max'] : null;
    }

    public function minItems(): int
    {
        return max(0, (int) ($this->config['min'] ?? 0));
    }

    /**
     * @return list<BlueprintField>
     */
    public function subFields(): array
    {
        $fields = [];

        foreach ($this->config['fields'] ?? [] as $fieldConfig) {
            if (! is_array($fieldConfig)) {
                continue;
            }

            $handle = (string) ($fieldConfig['handle'] ?? '');
            $type = (string) ($fieldConfig['type'] ?? 'text');

            if ($handle === '') {
                continue;
            }

            unset($fieldConfig['handle'], $fieldConfig['type']);

            $fields[] = new BlueprintField($handle, $type, $fieldConfig);
        }

        return $fields;
    }

    public function rows(): int
    {
        return (int) ($this->config['rows'] ?? 4);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'handle' => $this->handle,
            'type' => $this->type,
            'display' => $this->display(),
            'localizable' => $this->isLocalizable(),
            'required' => $this->isRequired(),
            'instructions' => $this->instructions(),
            'collection' => $this->collection(),
            'max' => $this->maxItems(),
            'min' => $this->minItems(),
            'rows' => $this->rows(),
            'sub_fields' => array_map(
                fn (BlueprintField $field): array => $field->toArray(),
                $this->subFields(),
            ),
        ];
    }
}
