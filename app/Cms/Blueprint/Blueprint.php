<?php

namespace App\Cms\Blueprint;

/**
 * Parsed blueprint schema for a CMS content type.
 */
readonly class Blueprint
{
    /**
     * @param  array<string, BlueprintSection>  $sections
     */
    public function __construct(
        public string $handle,
        public string $title,
        public array $sections,
    ) {}

    public function section(string $handle): ?BlueprintSection
    {
        return $this->sections[$handle] ?? null;
    }

    /**
     * @return list<BlueprintField>
     */
    public function fields(): array
    {
        return array_merge(...array_map(
            fn (BlueprintSection $section): array => $section->fields,
            array_values($this->sections),
        ));
    }

    public function field(string $handle): ?BlueprintField
    {
        foreach ($this->fields() as $field) {
            if ($field->handle === $handle) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'handle' => $this->handle,
            'title' => $this->title,
            'sections' => array_map(
                fn (BlueprintSection $section): array => $section->toArray(),
                $this->sections,
            ),
        ];
    }
}
