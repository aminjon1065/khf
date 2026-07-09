<?php

namespace App\Http\Resources\Api\V1;

use App\Cms\ContentTypeDefinition;
use App\Services\Api\V1\CollectionApiService;
use App\Services\Cms\TaxonomyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Model
 */
class CollectionEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Model $entry */
        $entry = $this->resource;
        /** @var ContentTypeDefinition $type */
        $type = $request->attributes->get('api_collection');
        $detailed = (bool) $request->attributes->get('api_detailed', false);
        $api = app(CollectionApiService::class);
        $translation = method_exists($entry, 'translation') ? $entry->translation() : null;

        $payload = [
            'id' => $entry->getKey(),
            'collection' => $type->handle,
            'title' => $this->resolveTitle($translation),
            'slug' => $translation?->getAttribute('slug'),
            'locales' => method_exists($entry, 'translatedLocales') ? $entry->translatedLocales() : [],
            'url' => $api->publicUrl($type, $entry),
        ];

        if (isset($entry->published_at)) {
            $payload['published_at'] = $entry->published_at?->toIso8601String();
        }

        if (isset($entry->status) && $type->hasFeature('editorial')) {
            $payload['status'] = [
                'value' => $entry->status->value,
                'label' => $entry->status->label(),
            ];
        }

        if ($type->handle === 'post' && isset($entry->type)) {
            $payload['type'] = [
                'value' => $entry->type->value,
                'label' => $entry->type->label(),
            ];
            $payload['category'] = $entry->category?->translation()?->getAttribute('name');
        }

        if (in_array($type->handle, ['post', 'page'], true)) {
            $payload['tags'] = app(TaxonomyService::class)->termsForModel($entry);
        }

        if ($detailed && $translation !== null) {
            $payload['fields'] = collect($translation->getAttributes())
                ->except(['id', 'created_at', 'updated_at'])
                ->all();
        } elseif ($translation !== null) {
            foreach (['excerpt', 'question'] as $summaryField) {
                $value = $translation->getAttribute($summaryField);

                if (is_string($value) && $value !== '') {
                    $payload[$summaryField] = $value;
                }
            }
        }

        return $payload;
    }

    private function resolveTitle(?Model $translation): ?string
    {
        if ($translation === null) {
            return null;
        }

        foreach (['title', 'name', 'question', 'full_name', 'label'] as $field) {
            $value = $translation->getAttribute($field);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
