<?php

namespace App\Cms\Blueprint;

use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses blueprint YAML files into {@see Blueprint} objects.
 */
class BlueprintParser
{
    public function parse(string $handle, string $yaml): Blueprint
    {
        /** @var array<string, mixed> $data */
        $data = Yaml::parse($yaml);

        if (! is_array($data)) {
            throw new InvalidArgumentException("Blueprint [{$handle}] must be a YAML mapping.");
        }

        $title = (string) ($data['title'] ?? str($handle)->headline()->toString());
        $sections = [];

        /** @var array<string, mixed> $sectionsData */
        $sectionsData = $data['sections'] ?? [];

        foreach ($sectionsData as $sectionHandle => $sectionConfig) {
            if (! is_array($sectionConfig)) {
                continue;
            }

            $fields = [];

            /** @var list<array<string, mixed>> $fieldsData */
            $fieldsData = $sectionConfig['fields'] ?? [];

            foreach ($fieldsData as $fieldConfig) {
                if (! is_array($fieldConfig)) {
                    continue;
                }

                $fieldHandle = (string) ($fieldConfig['handle'] ?? '');
                $fieldType = (string) ($fieldConfig['type'] ?? 'text');

                if ($fieldHandle === '') {
                    continue;
                }

                unset($fieldConfig['handle'], $fieldConfig['type']);

                $fields[] = new BlueprintField($fieldHandle, $fieldType, $fieldConfig);
            }

            unset($sectionConfig['fields']);

            $sections[(string) $sectionHandle] = new BlueprintSection(
                handle: (string) $sectionHandle,
                fields: $fields,
                config: $sectionConfig,
            );
        }

        return new Blueprint($handle, $title, $sections);
    }
}
