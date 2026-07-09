<?php

namespace App\Services\Admin;

use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Aggregates editorial content awaiting moderation across all CMS collections.
 */
class ModerationQueueService
{
    public function __construct(private ContentTypeRegistry $contentTypes) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(int $limit = 50): Collection
    {
        $locale = app()->getLocale();
        $items = collect();

        foreach ($this->contentTypes->editorial() as $type) {
            if (! $this->supportsModeration($type->modelClass)) {
                continue;
            }

            /** @var class-string<Model> $modelClass */
            $modelClass = $type->modelClass;

            $records = $modelClass::query()
                ->where('status', ContentStatus::Moderation)
                ->with('translations')
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get();

            foreach ($records as $record) {
                $items->push($this->toRow($type, $record, $locale));
            }
        }

        return $items
            ->sortByDesc('updated_at')
            ->take($limit)
            ->values();
    }

    public function count(): int
    {
        $total = 0;

        foreach ($this->contentTypes->editorial() as $type) {
            if (! $this->supportsModeration($type->modelClass)) {
                continue;
            }

            /** @var class-string<Model> $modelClass */
            $modelClass = $type->modelClass;

            $total += $modelClass::query()
                ->where('status', ContentStatus::Moderation)
                ->count();
        }

        return $total;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function supportsModeration(string $modelClass): bool
    {
        $model = new $modelClass;

        return ($model->getCasts()['status'] ?? null) === ContentStatus::class
            && method_exists($model, 'translations');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(ContentTypeDefinition $type, Model $record, string $locale): array
    {
        return [
            'id' => $record->getKey(),
            'content_type' => $type->handle,
            'content_type_label' => $type->label,
            'title' => $this->resolveTitle($record, $locale),
            'edit_url' => route("admin.{$type->routePrefix}.edit", $record),
            'updated_at' => $record->updated_at?->toIso8601String(),
        ];
    }

    private function resolveTitle(Model $record, string $locale): string
    {
        if (! method_exists($record, 'translation')) {
            return '—';
        }

        $translation = $record->translation($locale) ?? $record->translation();

        if ($translation === null) {
            return '—';
        }

        foreach (['title', 'name', 'question', 'full_name', 'label'] as $field) {
            $value = $translation->{$field} ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '—';
    }
}
