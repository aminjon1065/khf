<?php

namespace App\Cms\BlockSet;

/**
 * A configurable block type inside a block set.
 */
readonly class BlockTypeDefinition
{
    /**
     * @param  array<string, mixed>  $defaults
     */
    public function __construct(
        public string $type,
        public string $label,
        public array $defaults = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'defaults' => $this->defaults,
        ];
    }
}
