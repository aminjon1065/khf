<?php

namespace App\Cms\BlockSet;

use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses block set YAML files.
 */
class BlockSetParser
{
    public function parse(string $handle, string $yaml): BlockSet
    {
        /** @var array<string, mixed> $data */
        $data = Yaml::parse($yaml);

        if (! is_array($data)) {
            throw new InvalidArgumentException("Block set [{$handle}] must be a YAML mapping.");
        }

        $blocks = [];

        /** @var array<string, array<string, mixed>> $blocksData */
        $blocksData = $data['blocks'] ?? [];

        foreach ($blocksData as $type => $config) {
            $blocks[] = new BlockTypeDefinition(
                type: (string) $type,
                label: (string) ($config['label'] ?? str($type)->headline()->toString()),
                defaults: is_array($config['defaults'] ?? null) ? $config['defaults'] : [],
            );
        }

        return new BlockSet($handle, $blocks);
    }
}
