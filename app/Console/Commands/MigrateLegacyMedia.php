<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Leader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Best-effort download of the leader portraits and legal-act PDFs still hosted on khf.tj/kchs.tj
 * into the media library (WP-4b). Network-dependent, so it lives in a re-runnable command rather
 * than the seeders: it is idempotent (skips records that already have media), skips files the
 * harvest flagged as broken, and never aborts the whole run on a single failed download.
 *
 * Records are matched back to database/data/legacy/*.json by the sort_order stamped at seed time
 * (leaders: sort_order = order; documents: sort_order = the JSON array index).
 */
#[Signature('legacy:migrate-media {--only= : Restrict to "leaders" or "documents"}')]
#[Description('Download leader photos + document PDFs from the legacy sites into the media library')]
class MigrateLegacyMedia extends Command
{
    public function handle(): int
    {
        $only = $this->option('only');

        if ($only !== null && ! in_array($only, ['leaders', 'documents'], true)) {
            $this->error('--only must be "leaders" or "documents".');

            return self::FAILURE;
        }

        if ($only !== 'documents') {
            $this->migrateLeaderPhotos();
        }

        if ($only !== 'leaders') {
            $this->migrateDocumentFiles();
        }

        return self::SUCCESS;
    }

    private function migrateLeaderPhotos(): void
    {
        $this->info('Leader photos');
        $migrated = $skipped = $failed = 0;

        foreach ($this->legacy('structure.json')['leaders'] ?? [] as $data) {
            $url = $data['photoUrl'] ?? null;
            $leader = Leader::query()->where('sort_order', (int) ($data['order'] ?? 0))->first();

            if ($leader === null || empty($url)) {
                continue;
            }

            if ($leader->hasMedia(Leader::PHOTO_COLLECTION)) {
                $skipped++;

                continue;
            }

            try {
                $leader->addMediaFromUrl($url)
                    ->usingFileName($this->fileName($url, 'leader-'.$leader->id))
                    ->toMediaCollection(Leader::PHOTO_COLLECTION);
                $migrated++;
                $this->line("  ✓ #{$leader->id}");
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("  ✗ #{$leader->id}: ".Str::limit($e->getMessage(), 80));
            }
        }

        $this->line("  → migrated {$migrated}, skipped {$skipped}, failed {$failed}");
    }

    private function migrateDocumentFiles(): void
    {
        $this->info('Document files');
        $migrated = $skipped = $failed = 0;

        foreach ($this->legacy('legal-documents.json')['documents'] ?? [] as $index => $data) {
            $document = Document::query()->where('sort_order', $index)->first();

            if ($document === null) {
                continue;
            }

            if ($document->hasMedia(Document::FILES_COLLECTION)) {
                $skipped++;

                continue;
            }

            $attached = false;

            foreach ($this->documentFiles($data) as $file) {
                try {
                    $document->addMediaFromUrl($file['url'])
                        ->usingFileName($this->fileName($file['url'], 'doc-'.$document->id.'-'.$file['locale']))
                        ->withCustomProperties(['locale' => $file['locale']])
                        ->toMediaCollection(Document::FILES_COLLECTION);
                    $attached = true;
                } catch (\Throwable $e) {
                    $this->warn("  ✗ #{$document->id} ({$file['locale']}): ".Str::limit($e->getMessage(), 80));
                }
            }

            if ($attached) {
                $migrated++;
                $this->line("  ✓ #{$document->id}");
            } elseif ($this->documentFiles($data) !== []) {
                $failed++;
            }
        }

        $this->line("  → migrated {$migrated}, skipped {$skipped}, failed {$failed}");
    }

    /**
     * Non-broken files, deduplicated to the first one per locale (the legacy data lists several
     * near-duplicate URLs per act).
     *
     * @param  array<string, mixed>  $data
     * @return list<array{url: string, locale: string}>
     */
    private function documentFiles(array $data): array
    {
        $files = $data['files'] ?? [];

        if ($files === [] && ! empty($data['fileUrl'])) {
            $files = [['url' => $data['fileUrl'], 'locale' => 'tj', 'broken' => false]];
        }

        $byLocale = [];

        foreach ($files as $file) {
            $url = (string) ($file['url'] ?? '');
            $locale = (string) ($file['locale'] ?? 'tj');

            if ($url === '' || ($file['broken'] ?? false) === true || isset($byLocale[$locale])) {
                continue;
            }

            $byLocale[$locale] = ['url' => $url, 'locale' => $locale];
        }

        return array_values($byLocale);
    }

    private function fileName(string $url, string $fallback): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $base = urldecode(basename($path));
        $extension = Str::lower(pathinfo($base, PATHINFO_EXTENSION));
        $name = Str::slug(pathinfo($base, PATHINFO_FILENAME)) ?: $fallback;

        return $extension !== '' ? "{$name}.{$extension}" : $name;
    }

    /**
     * @return array<string, mixed>
     */
    private function legacy(string $file): array
    {
        $path = database_path('data/legacy/'.$file);

        if (! is_file($path)) {
            throw new RuntimeException("Legacy data file not found: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
