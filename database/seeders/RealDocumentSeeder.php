<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use Database\Seeders\Concerns\ReadsLegacyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Legal-acts registry harvested verbatim from khf.tj/kchs.tj (ТЗ §20«в», §6.8). Adoption dates and
 * numbers are kept only where the legacy sites published them — never guessed. The document number,
 * which has no column, is folded into `source`. Binary files are not downloaded here (the legacy
 * URLs are preserved in database/data/legacy/legal-documents.json for a later media-migration pass).
 */
class RealDocumentSeeder extends Seeder
{
    use ReadsLegacyData;

    public function run(): void
    {
        if (Document::withTrashed()->exists()) {
            return;
        }

        foreach ($this->legacyData('legal-documents.json')['documents'] ?? [] as $index => $data) {
            $translations = $this->presentTranslations(
                $data['translations'] ?? [],
                fn (string $locale, array $t): array => [
                    'name' => $t['name'] ?? '',
                    'description' => $t['description'] ?? null,
                ],
            );

            // A document with no name in any locale is not useful; skip it.
            if ($translations === []) {
                continue;
            }

            $document = Document::create([
                'type' => $this->resolveType($data['type'] ?? null),
                'source' => $data['number'] ?? $data['source'] ?? null,
                'document_date' => $this->parseDate($data['adoptedAt'] ?? null),
                'status' => ContentStatus::Published,
                'sort_order' => $index,
            ]);

            $document->upsertTranslations($translations);
        }
    }

    private function resolveType(?string $type): DocumentType
    {
        return DocumentType::tryFrom((string) $type) ?? DocumentType::Departmental;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
