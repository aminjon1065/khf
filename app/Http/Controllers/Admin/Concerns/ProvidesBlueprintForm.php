<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Cms\BlockSet\BlockSetRepository;
use App\Cms\Blueprint\Blueprint;
use App\Cms\Blueprint\BlueprintRepository;
use App\Cms\ContentTypeRegistry;

/**
 * Loads blueprint schema for Inertia CMS forms.
 */
trait ProvidesBlueprintForm
{
    /**
     * @return array{blueprint: array<string, mixed>}
     */
    protected function blueprintFormProps(string $contentTypeHandle): array
    {
        $contentType = app(ContentTypeRegistry::class)->get($contentTypeHandle);
        $blueprint = app(BlueprintRepository::class)->find($contentType->blueprint);

        return [
            'blueprint' => $blueprint->toArray(),
        ];
    }

    protected function loadBlueprint(string $reference): Blueprint
    {
        return app(BlueprintRepository::class)->find($reference);
    }

    /**
     * @return array{blockset?: array<string, mixed>}
     */
    protected function blocksetFormProps(?string $handle): array
    {
        if ($handle === null || ! app(BlockSetRepository::class)->exists($handle)) {
            return [];
        }

        return [
            'blockset' => app(BlockSetRepository::class)->find($handle)->toArray(),
        ];
    }
}
