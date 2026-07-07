<?php

namespace App\Support;

use App\Enums\ContentStatus;
use Carbon\Carbon;

/**
 * Normalises publication schedule fields for schedulable CMS content (ТЗ §6.2, §7.2).
 */
class PublicationScheduler
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $status = ContentStatus::from($data['status']);

        if ($status === ContentStatus::Published && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now()->format('Y-m-d\TH:i');
        }

        if ($status !== ContentStatus::Published) {
            $data['unpublished_at'] = null;
        }

        return $data;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function transitionOptions(ContentStatus $current): array
    {
        return array_map(
            fn (ContentStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            $current->allowedTransitions(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusOptions(): array
    {
        return array_map(
            fn (ContentStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            ContentStatus::cases(),
        );
    }

    public static function parseDateTime(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value);
    }
}
