<?php

namespace App\Support;

use App\Services\Cms\GlobalResolver;

class SocialLinks
{
    /**
     * @return list<array{platform: string, url: string}>
     */
    public static function all(): array
    {
        return app(GlobalResolver::class)->socialLinks();
    }
}
