<?php

namespace App\Enums;

/**
 * Subscription themes a user can opt into (ТЗ §6.4.3).
 */
enum SubscriptionTopic: string
{
    case Alerts = 'alerts';
    case News = 'news';
    case Announcements = 'announcements';

    public function label(): string
    {
        return __('enums.subscription_topic.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $topic): string => $topic->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $topic): array => [
            'value' => $topic->value,
            'label' => $topic->label(),
        ], self::cases());
    }
}
