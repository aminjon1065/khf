<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Subdivision;
use Database\Seeders\Concerns\ReadsLegacyData;
use Illuminate\Database\Seeder;

/**
 * Committee organisational structure harvested verbatim from khf.tj/kchs.tj (ТЗ §20«б»). The four
 * regional offices (Раёсати Душанбе/Хатлон/Суғд/ВМКБ) are already part of the subdivisions list, so
 * they are not seeded twice. Two-pass: create every node, then resolve the `parentKey` tree.
 */
class RealStructureSeeder extends Seeder
{
    use ReadsLegacyData;

    public function run(): void
    {
        if (Subdivision::query()->exists()) {
            return;
        }

        $subdivisions = $this->legacyData('structure.json')['subdivisions'] ?? [];
        $idByKey = [];

        // Pass 1 — create every subdivision without a parent link.
        foreach ($subdivisions as $data) {
            $subdivision = Subdivision::create([
                'status' => ContentStatus::Published,
                'parent_id' => null,
                'sort_order' => (int) ($data['order'] ?? 0),
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'staff_count' => $data['staff_count'] ?? null,
            ]);

            $subdivision->upsertTranslations($this->presentTranslations(
                $data['translations'] ?? [],
                fn (string $locale, array $t): array => [
                    'name' => $this->clip($t['name'] ?? '') ?? '',
                    'head' => $this->clip($t['head'] ?? null),
                    'functions' => $t['functions'] ?? null,
                    'address' => $this->clip($t['address'] ?? null),
                ],
            ));

            if (! empty($data['key'])) {
                $idByKey[$data['key']] = $subdivision->id;
            }
        }

        // Pass 2 — attach children to their parent now that every id is known.
        foreach ($subdivisions as $data) {
            $parentKey = $data['parentKey'] ?? null;

            if ($parentKey === null || empty($data['key'])) {
                continue;
            }

            if (isset($idByKey[$data['key']], $idByKey[$parentKey])) {
                Subdivision::whereKey($idByKey[$data['key']])->update(['parent_id' => $idByKey[$parentKey]]);
            }
        }
    }
}
