<?php

namespace App\Services\Admin;

/**
 * Builds a field-level diff between two revision payloads.
 */
class RevisionDiffBuilder
{
    /** @var list<string> */
    private const SKIP_KEYS = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'locale',
        'page_id',
        'post_id',
        'document_id',
        'guide_id',
        'gallery_id',
        'faq_id',
        'gov_service_id',
        'incident_id',
        'alert_id',
        'region_id',
        'notified_at',
    ];

    /** @var array<string, string> */
    private const LABELS = [
        'status' => 'Статус',
        'sort_order' => 'Порядок',
        'type' => 'Тип',
        'category' => 'Категория',
        'category_id' => 'Рубрика',
        'is_online' => 'Онлайн-услуга',
        'external_url' => 'Внешняя ссылка',
        'processing_time' => 'Срок обработки',
        'fee' => 'Стоимость',
        'published_at' => 'Дата публикации',
        'unpublished_at' => 'Дата снятия',
        'document_date' => 'Дата документа',
        'title' => 'Заголовок',
        'name' => 'Название',
        'slug' => 'URL',
        'question' => 'Вопрос',
        'answer' => 'Ответ',
        'excerpt' => 'Анонс',
        'summary' => 'Краткое описание',
        'body' => 'Содержание',
        'description' => 'Описание',
        'eligibility' => 'Кто может обратиться',
        'required_documents' => 'Необходимые документы',
        'seo_title' => 'SEO заголовок',
        'seo_description' => 'SEO описание',
        'blocks' => 'Блоки',
        'hazard_level' => 'Уровень опасности',
        'region_id' => 'Регион',
        'latitude' => 'Широта',
        'longitude' => 'Долгота',
        'occurred_at' => 'Дата события',
        'is_dismissible' => 'Можно закрыть',
        'starts_at' => 'Начало показа',
        'ends_at' => 'Окончание показа',
        'body' => 'Текст оповещения',
    ];

    /**
     * @return list<array{group: string, locale: string|null, field: string, label: string, before: string, after: string}>
     */
    public function diff(array $older, array $newer): array
    {
        $changes = [];

        foreach ($this->attributeChanges($older, $newer) as $change) {
            $changes[] = $change;
        }

        foreach ($this->translationChanges($older, $newer) as $change) {
            $changes[] = $change;
        }

        return $changes;
    }

    /**
     * @return list<array{group: string, locale: string|null, field: string, label: string, before: string, after: string}>
     */
    private function attributeChanges(array $older, array $newer): array
    {
        $changes = [];
        $oldAttributes = $older['attributes'] ?? [];
        $newAttributes = $newer['attributes'] ?? [];
        $keys = array_unique(array_merge(array_keys($oldAttributes), array_keys($newAttributes)));

        foreach ($keys as $key) {
            if (in_array($key, self::SKIP_KEYS, true)) {
                continue;
            }

            $before = $oldAttributes[$key] ?? null;
            $after = $newAttributes[$key] ?? null;

            if ($this->normalized($before) === $this->normalized($after)) {
                continue;
            }

            $changes[] = [
                'group' => 'attributes',
                'locale' => null,
                'field' => $key,
                'label' => $this->label($key),
                'before' => $this->format($before),
                'after' => $this->format($after),
            ];
        }

        return $changes;
    }

    /**
     * @return list<array{group: string, locale: string|null, field: string, label: string, before: string, after: string}>
     */
    private function translationChanges(array $older, array $newer): array
    {
        $changes = [];
        $oldByLocale = collect($older['translations'] ?? [])->keyBy('locale');
        $newByLocale = collect($newer['translations'] ?? [])->keyBy('locale');
        $locales = $oldByLocale->keys()->merge($newByLocale->keys())->unique()->values();

        foreach ($locales as $locale) {
            /** @var array<string, mixed> $old */
            $old = $oldByLocale->get($locale, []);
            /** @var array<string, mixed> $new */
            $new = $newByLocale->get($locale, []);
            $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

            foreach ($keys as $key) {
                if (in_array($key, self::SKIP_KEYS, true)) {
                    continue;
                }

                $before = $old[$key] ?? null;
                $after = $new[$key] ?? null;

                if ($this->normalized($before) === $this->normalized($after)) {
                    continue;
                }

                $changes[] = [
                    'group' => 'translations',
                    'locale' => (string) $locale,
                    'field' => $key,
                    'label' => $this->label($key),
                    'before' => $this->format($before),
                    'after' => $this->format($after),
                ];
            }
        }

        return $changes;
    }

    private function label(string $key): string
    {
        return self::LABELS[$key] ?? str($key)->headline()->toString();
    }

    private function normalized(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return trim((string) $value);
    }

    private function format(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Да' : 'Нет';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '—';
        }

        $string = trim(strip_tags((string) $value));

        if (mb_strlen($string) > 500) {
            return mb_substr($string, 0, 500).'…';
        }

        return $string !== '' ? $string : '—';
    }
}
