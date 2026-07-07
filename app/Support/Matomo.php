<?php

namespace App\Support;

class Matomo
{
    public static function isEnabled(): bool
    {
        return filled(config('matomo.url')) && filled(config('matomo.site_id'));
    }

    /**
     * Goal IDs exposed to the front end (only numeric, configured goals).
     *
     * @return array<string, int>
     */
    public static function goals(): array
    {
        if (! self::isEnabled()) {
            return [];
        }

        return collect(config('matomo.goals', []))
            ->filter(fn (mixed $id) => filled($id) && is_numeric($id))
            ->map(fn (mixed $id) => (int) $id)
            ->all();
    }

    /**
     * @return array{enabled: bool, goals: array<string, int>}
     */
    public static function inertiaProps(): array
    {
        return [
            'enabled' => self::isEnabled(),
            'goals' => self::goals(),
        ];
    }
}
