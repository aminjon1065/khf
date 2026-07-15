<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Models\Post;
use Database\Seeders\Concerns\ReadsLegacyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * News, press-releases and announcements harvested verbatim from khf.tj/kchs.tj (ТЗ §6.2). Most
 * legacy articles exist in a single language, so only the present locales get a translation. Each
 * post carries its Drupal `legacy_node_id` so the 301-redirect map (WP-3) can be generated later.
 */
class RealNewsSeeder extends Seeder
{
    use ReadsLegacyData;

    public function run(): void
    {
        if (Post::withTrashed()->exists()) {
            return;
        }

        foreach ($this->legacyData('news.json')['posts'] ?? [] as $index => $data) {
            $nodeId = $this->legacyNodeId($data['legacyUrl'] ?? []);

            $translations = $this->presentTranslations(
                $data['translations'] ?? [],
                fn (string $locale, array $t): array => [
                    'title' => $this->clip($t['title'] ?? '') ?? '',
                    'slug' => $this->slug($t['title'] ?? '', $nodeId ?? $index),
                    'excerpt' => $this->trim($t['excerpt'] ?? null, 500),
                    'body' => $t['body'] ?? null,
                ],
            );

            if ($translations === []) {
                continue;
            }

            $post = Post::create([
                'legacy_node_id' => $nodeId,
                'type' => PostType::tryFrom((string) ($data['type'] ?? '')) ?? PostType::News,
                'status' => ContentStatus::Published,
                'published_at' => $this->parseDate($data['publishedAt'] ?? null),
            ]);

            $post->upsertTranslations($translations);
        }
    }

    /**
     * @param  array<string, string|null>  $legacyUrl
     */
    private function legacyNodeId(array $legacyUrl): ?int
    {
        foreach ($legacyUrl as $url) {
            if (is_string($url) && preg_match('#/node/(\d+)#', $url, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function slug(string $title, int|string $suffix): string
    {
        $base = Str::limit(Str::tajikSlug($title), 180, '');

        return ($base !== '' ? $base : 'post').'-'.$suffix;
    }

    private function trim(?string $value, int $length): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Str::limit(trim($value), $length - 1, '…');
    }

    private function parseDate(?string $value): Carbon
    {
        try {
            return $value !== null ? Carbon::parse($value) : Carbon::now();
        } catch (\Throwable) {
            return Carbon::now();
        }
    }
}
