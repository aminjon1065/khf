<?php

namespace App\Cms\BlockSet;

/**
 * Parsed block set (available block types for a page builder field).
 */
readonly class BlockSet
{
    /**
     * @param  list<BlockTypeDefinition>  $blocks
     */
    public function __construct(
        public string $handle,
        public array $blocks,
    ) {}

    public function find(string $type): ?BlockTypeDefinition
    {
        foreach ($this->blocks as $block) {
            if ($block->type === $type) {
                return $block;
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
            'blocks' => array_map(
                fn (BlockTypeDefinition $block): array => $block->toArray(),
                $this->blocks,
            ),
        ];
    }
}
