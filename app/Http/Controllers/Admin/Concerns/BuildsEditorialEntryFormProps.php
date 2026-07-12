<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Cms\ContentTypeRegistry;

/**
 * Props for editorial CMS entry forms (autosave, preview, working copy, blocks).
 */
trait BuildsEditorialEntryFormProps
{
    use BuildsCmsEntryFormProps;

    /**
     * @param  array<string, mixed>|null  $entry
     * @param  array<string, mixed>  $fieldOptions
     * @param  array{
     *     publicUrls?: array<string, string>,
     *     previewUrls?: array<string, string>,
     *     hasUnpublishedChanges?: bool,
     *     blocksetHandle?: string|null,
     * }  $editorialMeta
     * @return array<string, mixed>
     */
    protected function editorialEntryFormProps(
        string $handle,
        ?array $entry,
        array $fieldOptions = [],
        array $editorialMeta = [],
    ): array {
        $id = $entry['id'] ?? null;
        $coverUrl = is_string($entry['cover_url'] ?? null) ? $entry['cover_url'] : null;
        $routePrefix = app(ContentTypeRegistry::class)->get($handle)->routePrefix;

        return [
            ...$this->contentEntryFormProps(
                $handle,
                $entry,
                $fieldOptions,
                ['coverUrl' => $coverUrl],
                [
                    'autosave' => $id !== null
                        ? route("admin.{$routePrefix}.autosave", $id)
                        : null,
                    'publishVersion' => $id !== null
                        ? route("admin.{$routePrefix}.publish-version", $id)
                        : null,
                ],
            ),
            'publicUrls' => $editorialMeta['publicUrls'] ?? [],
            'previewUrls' => $editorialMeta['previewUrls'] ?? [],
            'hasUnpublishedChanges' => $editorialMeta['hasUnpublishedChanges'] ?? false,
            ...$this->blocksetFormProps($editorialMeta['blocksetHandle'] ?? null),
        ];
    }
}
