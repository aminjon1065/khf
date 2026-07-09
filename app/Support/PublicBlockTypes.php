<?php

namespace App\Support;

/**
 * Public page block types rendered by the frontend block registry.
 *
 * @see resources/js/components/Public/blocks/registry.tsx
 */
class PublicBlockTypes
{
    /** @var list<string> */
    public const PAGE = [
        'text',
        'image_gallery',
        'news_list',
        'map_widget',
        'cta',
        'accordion',
        'table',
        'contacts',
    ];
}
