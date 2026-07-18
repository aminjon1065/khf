<?php

namespace App\Support;

/**
 * Sanitises page-builder block payloads before storage (ТЗ §7.3, §12.2 — XSS protection).
 *
 * @phpstan-type BlockArray array{id: string, type: string, data: array<string, mixed>}
 */
class BlockSanitizer
{
    public function __construct(private HtmlSanitizer $htmlSanitizer) {}

    /**
     * @param  list<BlockArray>|null  $blocks
     * @return list<BlockArray>|null
     */
    public function sanitize(?array $blocks): ?array
    {
        if ($blocks === null) {
            return null;
        }

        return array_values(array_map(
            fn (array $block): array => $this->sanitizeBlock($block),
            $blocks,
        ));
    }

    /**
     * @param  BlockArray  $block
     * @return BlockArray
     */
    private function sanitizeBlock(array $block): array
    {
        $type = (string) ($block['type'] ?? '');
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        $sanitizedData = match ($type) {
            'text' => [
                'content' => $this->htmlSanitizer->clean($data['content'] ?? null),
            ],
            'accordion' => [
                'items' => $this->sanitizeAccordionItems($data['items'] ?? []),
            ],
            'table' => [
                'caption' => $this->plainText($data['caption'] ?? null),
                'headers' => $this->sanitizeStringList($data['headers'] ?? []),
                'rows' => $this->sanitizeTableRows($data['rows'] ?? [], $data['headers'] ?? []),
            ],
            'contacts' => [
                'heading' => $this->plainText($data['heading'] ?? null),
                'address' => $this->plainText($data['address'] ?? null),
                'phone' => $this->plainText($data['phone'] ?? null),
                'email' => $this->plainText($data['email'] ?? null),
                'hours' => $this->plainText($data['hours'] ?? null),
            ],
            'image_gallery' => [
                'images' => $this->sanitizeGalleryImages($data['images'] ?? []),
            ],
            'map_widget' => [
                'lat' => $this->plainText($data['lat'] ?? null),
                'lng' => $this->plainText($data['lng'] ?? null),
                'zoom' => $this->plainText($data['zoom'] ?? null),
                'title' => $this->plainText($data['title'] ?? null),
            ],
            'cta' => [
                'label' => $this->plainText($data['label'] ?? null),
                'url' => $this->sanitizeUrl($data['url'] ?? null),
            ],
            'news_list' => [
                'count' => $this->plainText($data['count'] ?? null),
            ],
            default => [],
        };

        return [
            'id' => (string) ($block['id'] ?? ''),
            'type' => $type,
            'data' => $sanitizedData,
        ];
    }

    /**
     * @return list<array{title: string, content: string|null}>
     */
    private function sanitizeAccordionItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_map(
            function (mixed $item): array {
                $row = is_array($item) ? $item : [];

                return [
                    'title' => $this->plainText($row['title'] ?? null) ?? '',
                    'content' => $this->htmlSanitizer->clean($row['content'] ?? null),
                ];
            },
            $items,
        ));
    }

    /**
     * @return list<array{url: string, alt: string, caption: string}>
     */
    private function sanitizeGalleryImages(mixed $images): array
    {
        if (! is_array($images)) {
            return [];
        }

        return array_values(array_map(
            function (mixed $image): array {
                $row = is_array($image) ? $image : [];

                return [
                    'url' => $this->sanitizeUrl($row['url'] ?? null) ?? '',
                    'alt' => $this->plainText($row['alt'] ?? null) ?? '',
                    'caption' => $this->plainText($row['caption'] ?? null) ?? '',
                ];
            },
            $images,
        ));
    }

    /**
     * @return list<string>
     */
    private function sanitizeStringList(mixed $headers): array
    {
        if (! is_array($headers)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $header): string => $this->plainText($header) ?? '', $headers),
            fn (string $header): bool => $header !== '',
        ));
    }

    /**
     * @return list<list<string>>
     */
    private function sanitizeTableRows(mixed $rows, mixed $headers): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $columnCount = count($this->sanitizeStringList($headers));

        return array_values(array_map(
            function (mixed $row) use ($columnCount): array {
                $cells = is_array($row) ? $row : [];
                $sanitized = array_map(
                    fn (mixed $cell): string => $this->plainText($cell) ?? '',
                    $cells,
                );

                if ($columnCount > 0) {
                    $sanitized = array_slice(array_pad($sanitized, $columnCount, ''), 0, $columnCount);
                }

                return $sanitized;
            },
            $rows,
        ));
    }

    private function plainText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim(strip_tags((string) $value));

        return $text === '' ? '' : $text;
    }

    private function sanitizeUrl(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $url = trim((string) $value);

        if ($url === '') {
            return '';
        }

        if (str_starts_with(strtolower($url), 'javascript:')) {
            return '#';
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme === '' || in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return $url;
        }

        return '#';
    }
}
