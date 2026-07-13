<?php

namespace App\Services\Admin;

use App\Models\Redirect;
use App\Support\RedirectResolver;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Imports legacy URL → new URL mappings from CSV into the redirects table (ТЗ §15.1).
 *
 * CSV columns: from_path, to_url [, status_code] [, notes]
 */
class LegacyRedirectImporter
{
    /**
     * @return array{created: int, updated: int, skipped: int, total: int}
     */
    public function importFromCsv(string $path, bool $updateExisting = true, bool $dryRun = false): array
    {
        if (! is_readable($path)) {
            throw new InvalidArgumentException("CSV file is not readable: {$path}");
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV file: {$path}");
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false || $header === [null] || $header === []) {
                throw new InvalidArgumentException('CSV file is empty.');
            }

            $columns = $this->normalizeHeader($header);
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $data = $this->mapRow($columns, $row, $rowNumber);
                $fromPath = RedirectResolver::normalizePath($data['from_path']);
                $existing = Redirect::query()->where('from_path', $fromPath)->first();

                if ($existing !== null && ! $updateExisting) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    if ($existing !== null) {
                        $updated++;
                    } else {
                        $created++;
                    }

                    continue;
                }

                if ($existing !== null) {
                    $existing->update([
                        'to_url' => $data['to_url'],
                        'status_code' => $data['status_code'],
                        'is_active' => true,
                        'notes' => $data['notes'],
                    ]);
                    $updated++;

                    continue;
                }

                Redirect::query()->create([
                    'from_path' => $fromPath,
                    'to_url' => $data['to_url'],
                    'status_code' => $data['status_code'],
                    'is_active' => true,
                    'notes' => $data['notes'],
                ]);
                $created++;
            }
        } finally {
            fclose($handle);
        }

        if (! $dryRun) {
            RedirectResolver::clearCache();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $created + $updated + $skipped,
        ];
    }

    /**
     * @param  list<string|null>  $header
     * @return array{from_path: int, to_url: int, status_code: int|null, notes: int|null}
     */
    private function normalizeHeader(array $header): array
    {
        $normalized = [];

        foreach ($header as $index => $name) {
            $key = strtolower(trim((string) $name));
            $normalized[$key] = $index;
        }

        if (! isset($normalized['from_path'], $normalized['to_url'])) {
            throw new InvalidArgumentException(
                'CSV header must include from_path and to_url columns.',
            );
        }

        return [
            'from_path' => $normalized['from_path'],
            'to_url' => $normalized['to_url'],
            'status_code' => $normalized['status_code'] ?? null,
            'notes' => $normalized['notes'] ?? null,
        ];
    }

    /**
     * @param  array{from_path: int, to_url: int, status_code: int|null, notes: int|null}  $columns
     * @param  list<string|null>  $row
     * @return array{from_path: string, to_url: string, status_code: int, notes: string|null}
     */
    private function mapRow(array $columns, array $row, int $rowNumber): array
    {
        $fromPath = trim((string) ($row[$columns['from_path']] ?? ''));
        $toUrl = trim((string) ($row[$columns['to_url']] ?? ''));
        $statusRaw = $columns['status_code'] !== null
            ? trim((string) ($row[$columns['status_code']] ?? ''))
            : '';
        $notesRaw = $columns['notes'] !== null
            ? trim((string) ($row[$columns['notes']] ?? ''))
            : '';

        $payload = [
            'from_path' => $fromPath,
            'to_url' => $toUrl,
            'status_code' => $statusRaw !== '' ? (int) $statusRaw : 301,
            'notes' => $notesRaw !== '' ? $notesRaw : null,
        ];

        $validator = Validator::make($payload, [
            'from_path' => ['required', 'string', 'max:500', 'not_regex:/^https?:\/\//i'],
            'to_url' => ['required', 'string', 'max:2000'],
            'status_code' => ['integer', 'in:301,302'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(
                "Invalid CSV row {$rowNumber}: ".$validator->errors()->first(),
            );
        }

        /** @var array{from_path: string, to_url: string, status_code: int, notes: string|null} $validated */
        $validated = $validator->validated();

        return $validated;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
