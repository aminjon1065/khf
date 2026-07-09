<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

/**
 * Signed admin URLs for live preview of unpublished CMS content.
 */
class PreviewUrls
{
    private const EXPIRY_MINUTES = 120;

    /**
     * @return array<string, string>
     */
    public function forPage(int $pageId): array
    {
        return $this->forType('page', $pageId);
    }

    /**
     * @return array<string, string>
     */
    public function forPost(int $postId): array
    {
        return $this->forType('post', $postId);
    }

    /**
     * Locale switch map + hreflang alternates for preview mode.
     *
     * @param  array<string, string>  $slugByLocale
     * @return array{switch: array<string, string>, alternates: list<array{code: string, hreflang: string, url: string}>}
     */
    public function contentUrls(string $type, int $id, array $slugByLocale): array
    {
        $localeUrls = app(LocaleUrls::class);
        $switch = [];
        $alternates = [];

        foreach ($localeUrls->supportedCodes() as $code) {
            $url = isset($slugByLocale[$code])
                ? $this->signedUrl($type, $id, $code)
                : url($code);

            $switch[$code] = $url;
            $alternates[] = ['code' => $code, 'hreflang' => $localeUrls->hreflang($code), 'url' => $url];
        }

        return ['switch' => $switch, 'alternates' => $alternates];
    }

    /**
     * @return array<string, string>
     */
    private function forType(string $type, int $id): array
    {
        $urls = [];

        foreach (app(LocaleUrls::class)->supportedCodes() as $locale) {
            $urls[$locale] = $this->signedUrl($type, $id, $locale);
        }

        return $urls;
    }

    private function signedUrl(string $type, int $id, string $locale): string
    {
        return URL::temporarySignedRoute(
            'admin.preview.show',
            now()->addMinutes(self::EXPIRY_MINUTES),
            ['type' => $type, 'id' => $id, 'locale' => $locale],
        );
    }
}
