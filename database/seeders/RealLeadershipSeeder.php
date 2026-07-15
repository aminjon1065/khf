<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Leader;
use Database\Seeders\Concerns\ReadsLegacyData;
use Illuminate\Database\Seeder;

/**
 * Committee leadership harvested verbatim from khf.tj/kchs.tj (ТЗ §20«г»). Tajik + Russian only —
 * the legacy sites publish no English, so `en` stays absent (the UI falls back). Rank is not stored
 * separately (no column); it already appears inside the harvested biography prose.
 */
class RealLeadershipSeeder extends Seeder
{
    use ReadsLegacyData;

    public function run(): void
    {
        if (Leader::query()->exists()) {
            return;
        }

        foreach ($this->legacyData('structure.json')['leaders'] ?? [] as $data) {
            $leader = Leader::create([
                'status' => ContentStatus::Published,
                'sort_order' => (int) ($data['order'] ?? 0),
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            $leader->upsertTranslations($this->presentTranslations(
                $data['translations'] ?? [],
                fn (string $locale, array $t): array => [
                    'full_name' => $this->clip($t['full_name'] ?? '') ?? '',
                    'position' => $this->clip($t['position'] ?? '') ?? '',
                    'bio' => $t['bio'] ?? null,
                    'reception' => null,
                ],
            ));
        }
    }
}
