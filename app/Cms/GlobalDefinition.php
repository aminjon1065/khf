<?php

namespace App\Cms;

/**
 * Config-driven descriptor for a CMS global set.
 */
readonly class GlobalDefinition
{
    /**
     * @param  array<string, mixed>  $fallback
     */
    public function __construct(
        public string $handle,
        public string $label,
        public string $blueprint,
        public array $fallback = [],
        public string $icon = 'settings',
    ) {}

    public function editRoute(): string
    {
        return "admin.globals.{$this->handle}.edit";
    }
}
