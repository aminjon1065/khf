<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\SiteGlobal;
use Database\Seeders\Concerns\ReadsLegacyData;
use Illuminate\Database\Seeder;

/**
 * Real social channels + copyright harvested from khf.tj/kchs.tj into the site globals (ТЗ §6.9,
 * §15). Deprecated links (e.g. the old Telegram joinchat) are skipped in favour of the canonical
 * handle. The published emergency/reception numbers are intentionally NOT written to
 * `footer.hotline` — that CTA must be confirmed by the Committee before launch (D-… / audit WP-8).
 */
class RealContactsSeeder extends Seeder
{
    use ReadsLegacyData;

    public function run(): void
    {
        $contacts = $this->legacyData('contacts.json');
        $locale = Language::defaultCode();

        $this->updateGlobal('social', $locale, $this->socialLinks($contacts['socialLinks'] ?? []));

        $copyright = $contacts['officialName']['ru'] ?? $contacts['officialName']['tj'] ?? null;

        if ($copyright !== null && $copyright !== '') {
            $this->mergeGlobal('footer', $locale, ['copyright' => $copyright]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $links
     * @return array<string, string>
     */
    private function socialLinks(array $links): array
    {
        $map = [];

        foreach ($links as $link) {
            $platform = (string) ($link['platform'] ?? '');
            $url = (string) ($link['url'] ?? '');
            $note = (string) ($link['note'] ?? '');

            if ($platform === '' || $url === '' || stripos($note, 'deprecated') !== false || stripos($note, 'legacy') !== false) {
                continue;
            }

            // Keep the first canonical URL per platform.
            $map[$platform] ??= $url;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateGlobal(string $handle, string $locale, array $data): void
    {
        $global = SiteGlobal::query()->firstWhere('handle', $handle);

        if ($global === null) {
            return;
        }

        $existing = $global->translation($locale)?->data ?? [];
        $global->upsertTranslations([$locale => ['data' => [...$existing, ...$data]]]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function mergeGlobal(string $handle, string $locale, array $data): void
    {
        $this->updateGlobal($handle, $locale, $data);
    }
}
