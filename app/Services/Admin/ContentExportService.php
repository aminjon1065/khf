<?php

namespace App\Services\Admin;

use App\Cms\ContentTypeDefinition;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentExportService
{
    public function __construct(private ContentBrowserService $browser) {}

    /**
     * @param  array{search: string, sort: string, direction: string, status: string|null, trashed: bool}  $filters
     * @param  list<int>|null  $ids
     * @return array<string, mixed>
     */
    public function toJsonPayload(ContentTypeDefinition $type, array $filters, ?array $ids = null): array
    {
        $entries = $this->records($type, $filters, $ids)
            ->map(fn (Model $record): array => $this->serializeEntry($type, $record))
            ->values()
            ->all();

        return [
            'schema' => 'khf-cms-export',
            'version' => 1,
            'collection' => $type->handle,
            'label' => $type->label,
            'exported_at' => now()->toIso8601String(),
            'count' => count($entries),
            'entries' => $entries,
        ];
    }

    /**
     * @param  array{search: string, sort: string, direction: string, status: string|null, trashed: bool}  $filters
     * @param  list<int>|null  $ids
     */
    public function toJsonDownload(ContentTypeDefinition $type, array $filters, ?array $ids = null): StreamedResponse
    {
        $payload = $this->toJsonPayload($type, $filters, $ids);
        $filename = "{$type->handle}-export-".now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            function () use ($payload): void {
                echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            },
            $filename,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    /**
     * @param  array{search: string, sort: string, direction: string, status: string|null, trashed: bool}  $filters
     * @param  list<int>|null  $ids
     */
    public function toCsvDownload(ContentTypeDefinition $type, array $filters, ?array $ids = null): StreamedResponse
    {
        $records = $this->records($type, $filters, $ids);
        $filename = "{$type->handle}-export-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($type, $records): void {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, $this->csvHeaders($type), ';');

            foreach ($records as $record) {
                foreach ($this->csvRowsForEntry($type, $record) as $row) {
                    fputcsv($file, $row, ';');
                }
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array{search: string, sort: string, direction: string, status: string|null, trashed: bool}  $filters
     * @param  list<int>|null  $ids
     * @return Collection<int, Model>
     */
    private function records(ContentTypeDefinition $type, array $filters, ?array $ids): Collection
    {
        abort_unless($type->hasFeature('translations'), 404);

        $query = $this->browser->entryQuery($type, $filters);

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        return $query->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEntry(ContentTypeDefinition $type, Model $record): array
    {
        $attributes = collect($record->getAttributes())
            ->except([
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
                'published_snapshot',
                'published_snapshot_at',
            ])
            ->map(fn (mixed $value): mixed => $this->normalizeValue($value))
            ->all();

        $attributes = array_intersect_key($attributes, array_flip($record->getFillable()));

        $translations = [];

        if (method_exists($record, 'translations')) {
            $foreignKey = $this->translationForeignKey($record);

            foreach ($record->translations as $translation) {
                $translations[$translation->locale] = collect($translation->getAttributes())
                    ->except(['id', $foreignKey, 'created_at', 'updated_at'])
                    ->map(fn (mixed $value): mixed => $this->normalizeValue($value))
                    ->all();
            }
        }

        return [
            'id' => $record->getKey(),
            'attributes' => $attributes,
            'translations' => $translations,
        ];
    }

    /**
     * @return list<string>
     */
    private function csvHeaders(ContentTypeDefinition $type): array
    {
        return [
            'entry_id',
            'status',
            'locale',
            'title',
            'slug',
            'excerpt',
            'content',
            'question',
            'answer',
            'name',
            'full_name',
        ];
    }

    /**
     * @return list<list<string|null>>
     */
    private function csvRowsForEntry(ContentTypeDefinition $type, Model $record): array
    {
        $status = isset($record->status) ? (string) $record->status->value : null;
        $rows = [];

        if (! method_exists($record, 'translations') || $record->translations->isEmpty()) {
            return [[
                (string) $record->getKey(),
                $status,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ]];
        }

        foreach ($record->translations as $translation) {
            $rows[] = [
                (string) $record->getKey(),
                $status,
                $translation->locale,
                $this->stringOrNull($translation->title ?? null),
                $this->stringOrNull($translation->slug ?? null),
                $this->stringOrNull($translation->excerpt ?? null),
                $this->stringOrNull($translation->content ?? $translation->body ?? null),
                $this->stringOrNull($translation->question ?? null),
                $this->stringOrNull($translation->answer ?? null),
                $this->stringOrNull($translation->name ?? null),
                $this->stringOrNull($translation->full_name ?? null),
            ];
        }

        return $rows;
    }

    private function translationForeignKey(Model $record): string
    {
        if (property_exists($record, 'translationForeignKey')) {
            return $record->translationForeignKey;
        }

        return Str::snake(class_basename($record)).'_id';
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
