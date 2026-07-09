<?php

namespace App\Cms\Blueprint;

use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * Serializes blueprint builder payloads to YAML and validates round-trips.
 */
class BlueprintSerializer
{
    public function __construct(private BlueprintParser $parser) {}

    /**
     * @param  array{title: string, sections: array<string, array{handle?: string, display?: string, fields?: list<array<string, mixed>>}>}  $schema
     */
    public function toYaml(string $handle, array $schema): string
    {
        $title = trim((string) ($schema['title'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('Укажите название blueprint.');
        }

        /** @var array<string, mixed> $sections */
        $sections = $schema['sections'] ?? [];

        if ($sections === []) {
            throw new InvalidArgumentException('Добавьте хотя бы одну секцию.');
        }

        $data = [
            'title' => $title,
            'sections' => [],
        ];

        foreach ($sections as $sectionHandle => $section) {
            if (! is_array($section)) {
                continue;
            }

            $handleKey = (string) ($section['handle'] ?? $sectionHandle);

            if ($handleKey === '') {
                continue;
            }

            $sectionData = [];

            $display = trim((string) ($section['display'] ?? ''));

            if ($display !== '') {
                $sectionData['display'] = $display;
            }

            /** @var list<array<string, mixed>> $fields */
            $fields = $section['fields'] ?? [];

            $sectionData['fields'] = array_values(array_filter(array_map(
                fn (mixed $field): ?array => is_array($field) ? $this->fieldToYamlArray($field) : null,
                $fields,
            )));

            if ($sectionData['fields'] === []) {
                throw new InvalidArgumentException("Секция [{$handleKey}] должна содержать хотя бы одно поле.");
            }

            $data['sections'][$handleKey] = $sectionData;
        }

        if ($data['sections'] === []) {
            throw new InvalidArgumentException('Добавьте хотя бы одну секцию.');
        }

        $yaml = Yaml::dump(
            $data,
            8,
            2,
            Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK,
        );

        $this->parser->parse($handle, $yaml);

        return $this->normalizeLineEndings($yaml);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function fieldToYamlArray(array $field): array
    {
        $handle = trim((string) ($field['handle'] ?? ''));
        $type = trim((string) ($field['type'] ?? 'text'));

        if ($handle === '') {
            throw new InvalidArgumentException('У каждого поля должен быть handle.');
        }

        $item = [
            'handle' => $handle,
            'type' => $type,
        ];

        $display = trim((string) ($field['display'] ?? ''));

        if ($display !== '') {
            $item['display'] = $display;
        }

        if (($field['localizable'] ?? false) === true) {
            $item['localizable'] = true;
        }

        if (($field['required'] ?? false) === true) {
            $item['required'] = true;
        }

        $instructions = trim((string) ($field['instructions'] ?? ''));

        if ($instructions !== '') {
            $item['instructions'] = $instructions;
        }

        $collection = trim((string) ($field['collection'] ?? ''));

        if ($collection !== '') {
            $item['collection'] = $collection;
        }

        if (array_key_exists('max', $field) && $field['max'] !== null && $field['max'] !== '') {
            $item['max'] = (int) $field['max'];
        }

        if (array_key_exists('min', $field) && (int) $field['min'] > 0) {
            $item['min'] = (int) $field['min'];
        }

        if (array_key_exists('rows', $field) && (int) $field['rows'] > 0 && (int) $field['rows'] !== 4) {
            $item['rows'] = (int) $field['rows'];
        }

        /** @var list<array<string, mixed>> $subFields */
        $subFields = $field['sub_fields'] ?? [];

        if ($subFields !== []) {
            $item['fields'] = array_values(array_map(
                fn (array $subField): array => $this->subFieldToYamlArray($subField),
                array_filter($subFields, is_array(...)),
            ));
        }

        return $item;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function subFieldToYamlArray(array $field): array
    {
        $handle = trim((string) ($field['handle'] ?? ''));
        $type = trim((string) ($field['type'] ?? 'text'));

        if ($handle === '') {
            throw new InvalidArgumentException('У каждого вложенного поля должен быть handle.');
        }

        $item = [
            'handle' => $handle,
            'type' => $type,
        ];

        $display = trim((string) ($field['display'] ?? ''));

        if ($display !== '') {
            $item['display'] = $display;
        }

        if (array_key_exists('rows', $field) && (int) $field['rows'] > 0 && (int) $field['rows'] !== 4) {
            $item['rows'] = (int) $field['rows'];
        }

        return $item;
    }

    private function normalizeLineEndings(string $yaml): string
    {
        $normalized = str_replace("\r\n", "\n", $yaml);

        return rtrim($normalized, "\n")."\n";
    }
}
