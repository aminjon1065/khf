<?php

namespace App\Cms;

/**
 * Immutable descriptor for a CMS content type (collection entry).
 */
readonly class ContentTypeDefinition
{
    /**
     * @param  list<string>  $features
     * @param  list<string>  $sortable
     */
    public function __construct(
        public string $handle,
        public string $label,
        public string $modelClass,
        public string $blueprint,
        public string $routePrefix,
        public string $managePermission,
        public array $features = [],
        public array $sortable = [],
        public string $defaultSort = 'created_at',
        public string $defaultSortDirection = 'desc',
        public ?string $viewPermission = null,
        public string $icon = 'file-text',
        public string $listSearchField = 'title',
    ) {}

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    public function browserRoute(): string
    {
        return 'admin.content.index';
    }

    public function indexRoute(): string
    {
        return "admin.{$this->routePrefix}.index";
    }

    public function trashRoute(): string
    {
        return "admin.{$this->routePrefix}.trash";
    }
}
