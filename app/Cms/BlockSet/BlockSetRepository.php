<?php

namespace App\Cms\BlockSet;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Loads block sets from {@see resource_path('blocksets')}.
 */
class BlockSetRepository
{
    public function __construct(private BlockSetParser $parser) {}

    public function find(string $handle): BlockSet
    {
        $path = resource_path("blocksets/{$handle}.yaml");

        if (! File::exists($path)) {
            throw new InvalidArgumentException("Block set [{$handle}] not found at [{$path}].");
        }

        return $this->parser->parse($handle, File::get($path));
    }

    public function exists(string $handle): bool
    {
        return File::exists(resource_path("blocksets/{$handle}.yaml"));
    }
}
