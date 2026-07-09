<?php

namespace App\Cms\Blueprint;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Loads blueprint YAML files from {@see resource_path('blueprints')}.
 */
class BlueprintRepository
{
    public function __construct(private BlueprintParser $parser) {}

    public function find(string $reference): Blueprint
    {
        [$collection, $name] = array_pad(explode('.', $reference, 2), 2, 'default');

        if ($name === '') {
            $name = 'default';
        }

        $path = resource_path("blueprints/{$collection}/{$name}.yaml");

        if (! File::exists($path)) {
            throw new InvalidArgumentException("Blueprint [{$reference}] not found at [{$path}].");
        }

        return $this->parser->parse($reference, File::get($path));
    }

    public function exists(string $reference): bool
    {
        [$collection, $name] = array_pad(explode('.', $reference, 2), 2, 'default');

        if ($name === '') {
            $name = 'default';
        }

        return File::exists(resource_path("blueprints/{$collection}/{$name}.yaml"));
    }

    /**
     * @return list<array{handle: string, collection: string, name: string, title: string, field_count: int, section_count: int}>
     */
    public function all(): array
    {
        $blueprints = [];

        foreach (File::allFiles(resource_path('blueprints')) as $file) {
            if ($file->getExtension() !== 'yaml') {
                continue;
            }

            $collection = basename($file->getPath());
            $name = $file->getFilenameWithoutExtension();
            $reference = "{$collection}.{$name}";

            $parsed = $this->find($reference);

            $blueprints[] = [
                'handle' => $reference,
                'collection' => $collection,
                'name' => $name,
                'title' => $parsed->title,
                'field_count' => count($parsed->fields()),
                'section_count' => count($parsed->sections),
            ];
        }

        usort(
            $blueprints,
            fn (array $left, array $right): int => $left['handle'] <=> $right['handle'],
        );

        return $blueprints;
    }

    public function sourcePath(string $collection, string $name = 'default'): string
    {
        return resource_path("blueprints/{$collection}/{$name}.yaml");
    }

    public function write(string $collection, string $name, string $yaml): void
    {
        $reference = "{$collection}.{$name}";
        $path = $this->sourcePath($collection, $name);

        abort_unless(File::exists($path), 404);

        $this->parser->parse($reference, $yaml);

        $temporaryPath = "{$path}.tmp";

        File::put($temporaryPath, $this->normalizeLineEndings($yaml));
        File::move($temporaryPath, $path);
    }

    private function normalizeLineEndings(string $yaml): string
    {
        $normalized = str_replace("\r\n", "\n", $yaml);

        return rtrim($normalized, "\n")."\n";
    }
}
