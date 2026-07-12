<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Cms\ContentTypeRegistry;
use App\Enums\ContentStatus;

/**
 * Normalized Inertia props for the shared Entry Browser create/edit page.
 *
 * @phpstan-type EntryFormUrls array{back: string, store: string, update: string|null}
 */
trait BuildsCmsEntryFormProps
{
    /**
     * @param  array<string, mixed>|null  $entry
     * @param  array<string, mixed>  $fieldOptions
     * @param  array{existingFiles?: list<array<string, mixed>>, existingPhotos?: list<array<string, mixed>>, photoUrl?: string|null, coverUrl?: string|null, regionCoordinates?: array<int|string, array{lat: float, lng: float}>}  $assetMeta
     * @param  array<string, string>  $urlExtras
     * @return array<string, mixed>
     */
    protected function contentEntryFormProps(
        string $handle,
        ?array $entry,
        array $fieldOptions = [],
        array $assetMeta = [],
        array $urlExtras = [],
    ): array {
        $type = app(ContentTypeRegistry::class)->get($handle);
        $status = isset($entry['status']) && is_string($entry['status'])
            ? ContentStatus::tryFrom($entry['status'])
            : null;

        return [
            'entry' => $entry,
            'contentType' => [
                'handle' => $type->handle,
                'label' => $type->label,
                'titleField' => $type->listSearchField,
                'features' => $type->features,
            ],
            'urls' => [
                'back' => route($type->browserRoute(), $type->handle),
                'store' => route("admin.{$type->routePrefix}.store"),
                'update' => isset($entry['id'])
                    ? route("admin.{$type->routePrefix}.update", $entry['id'])
                    : null,
                ...$urlExtras,
            ],
            ...$this->publicationFormMeta($status),
            ...$this->blueprintFormProps($handle),
            'fieldOptions' => $fieldOptions,
            'locales' => $this->localeOptions(),
            ...$assetMeta,
        ];
    }
}
