<?php

namespace App\Services\Admin;

use App\Cms\ContentTypeDefinition;
use App\Enums\ContentStatus;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ContentImportService
{
    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function importJson(ContentTypeDefinition $type, array $payload, bool $updateExisting = false): array
    {
        $this->assertTranslatable($type);
        $this->validateJsonPayload($type, $payload);

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($type, $payload, $updateExisting, &$stats): void {
            foreach ($payload['entries'] as $entry) {
                if (! is_array($entry)) {
                    $stats['skipped']++;

                    continue;
                }

                $result = $this->importEntry($type, $entry, $updateExisting);
                $stats[$result]++;
            }
        });

        return $stats;
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function importCsv(ContentTypeDefinition $type, string $contents, bool $updateExisting = false): array
    {
        $this->assertTranslatable($type);

        $rows = $this->parseCsv($contents);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => __('The CSV file does not contain any importable rows.'),
            ]);
        }

        $grouped = [];

        foreach ($rows as $row) {
            $entryId = $row['entry_id'] ?? null;
            $locale = $row['locale'] ?? null;

            if (! is_string($locale) || $locale === '') {
                continue;
            }

            $groupKey = $entryId !== null && $entryId !== '' ? 'id:'.$entryId : 'new:'.md5(json_encode($row));

            $grouped[$groupKey]['id'] = is_numeric($entryId) ? (int) $entryId : null;
            $grouped[$groupKey]['attributes']['status'] = $row['status'] ?? ContentStatus::Draft->value;
            $grouped[$groupKey]['translations'][$locale] = $this->translationFromCsvRow($row);
        }

        $entries = array_map(
            fn (array $group): array => [
                'id' => $group['id'],
                'attributes' => $group['attributes'],
                'translations' => $group['translations'],
            ],
            array_values($grouped),
        );

        return $this->importJson($type, [
            'schema' => 'khf-cms-export',
            'version' => 1,
            'collection' => $type->handle,
            'entries' => $entries,
        ], $updateExisting);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateJsonPayload(ContentTypeDefinition $type, array $payload): void
    {
        if (($payload['schema'] ?? null) !== 'khf-cms-export') {
            throw ValidationException::withMessages([
                'file' => __('Unrecognised export format.'),
            ]);
        }

        if (($payload['collection'] ?? null) !== $type->handle) {
            throw ValidationException::withMessages([
                'file' => __('The export file belongs to another collection.'),
            ]);
        }

        if (! isset($payload['entries']) || ! is_array($payload['entries'])) {
            throw ValidationException::withMessages([
                'file' => __('The export file does not contain any entries.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function importEntry(ContentTypeDefinition $type, array $entry, bool $updateExisting): string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;
        $translations = is_array($entry['translations'] ?? null) ? $entry['translations'] : [];

        if ($translations === []) {
            return 'skipped';
        }

        $record = null;

        if ($updateExisting && isset($entry['id']) && is_numeric($entry['id'])) {
            $record = $modelClass::query()->whereKey((int) $entry['id'])->first();
        }

        $attributes = $this->normalizeAttributes($type, is_array($entry['attributes'] ?? null) ? $entry['attributes'] : []);

        if ($record === null) {
            $record = $modelClass::create($attributes);
            $record->upsertTranslations($translations);

            return 'created';
        }

        $record->update($attributes);
        $record->upsertTranslations($translations);

        return 'updated';
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(ContentTypeDefinition $type, array $attributes): array
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;
        $model = new $modelClass;
        $fillable = array_flip($model->getFillable());
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (! isset($fillable[$key])) {
                continue;
            }

            $normalized[$key] = $this->castAttribute($model, $key, $value);
        }

        if ($type->handle === 'post' && ! isset($normalized['type'])) {
            $normalized['type'] = 'news';
        }

        if ($this->supportsContentStatus($type) && ! isset($normalized['status'])) {
            $normalized['status'] = ContentStatus::Draft->value;
        }

        return $normalized;
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parseCsv(string $contents): array
    {
        $contents = ltrim($contents, "\xEF\xBB\xBF");
        $firstLine = strtok($contents, "\n") ?: '';
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $headers = fgetcsv($stream, 0, $delimiter);

        if ($headers === false) {
            fclose($stream);

            return [];
        }

        $headers = array_map(fn (?string $header): string => Str::snake(trim((string) $header)), $headers);
        $rows = [];

        while (($values = fgetcsv($stream, 0, $delimiter)) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? trim((string) $values[$index]) : null;
            }

            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     */
    private function translationFromCsvRow(array $row): array
    {
        $translation = [];

        foreach (['title', 'slug', 'excerpt', 'content', 'question', 'answer', 'name', 'full_name'] as $field) {
            $value = $row[$field] ?? null;

            if (is_string($value) && $value !== '') {
                $translation[$field] = $value;
            }
        }

        return $translation;
    }

    private function castAttribute(Model $model, string $key, mixed $value): mixed
    {
        $cast = $model->getCasts()[$key] ?? null;

        if ($cast === ContentStatus::class && is_string($value)) {
            return ContentStatus::from($value);
        }

        if (is_string($cast) && enum_exists($cast) && is_subclass_of($cast, BackedEnum::class) && is_string($value)) {
            return $cast::from($value);
        }

        if (in_array($cast, ['datetime', 'immutable_datetime'], true) && is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        if ($cast === 'array' && is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $value;
    }

    private function supportsContentStatus(ContentTypeDefinition $type): bool
    {
        $model = new $type->modelClass;

        return ($model->getCasts()['status'] ?? null) === ContentStatus::class;
    }

    private function assertTranslatable(ContentTypeDefinition $type): void
    {
        if (! $type->hasFeature('translations')) {
            throw new InvalidArgumentException("Collection [{$type->handle}] does not support import/export.");
        }
    }
}
