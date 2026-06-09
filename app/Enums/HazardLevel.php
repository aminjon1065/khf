<?php

namespace App\Enums;

/**
 * Hazard / danger level (ТЗ Приложение Г). Drives the alert banner, map markers and incident
 * statuses. Colour is always paired with a text label + icon (accessibility, §11.6). Values map to
 * the `hazard-*` design tokens.
 */
enum HazardLevel: string
{
    case Normal = 'normal';
    case Elevated = 'elevated';
    case Danger = 'danger';
    case Critical = 'critical';

    /**
     * Russian label for the CMS / UI (§7.1).
     */
    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Норма',
            self::Elevated => 'Повышенная готовность',
            self::Danger => 'Опасно',
            self::Critical => 'Чрезвычайная опасность',
        };
    }

    /**
     * Hex colour (Приложение Г).
     */
    public function color(): string
    {
        return match ($this) {
            self::Normal => '#16A34A',
            self::Elevated => '#EAB308',
            self::Danger => '#EA580C',
            self::Critical => '#DC2626',
        };
    }

    /**
     * Matching `hazard-*` design token suffix.
     */
    public function token(): string
    {
        return $this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $level): string => $level->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $level): array => [
            'value' => $level->value,
            'label' => $level->label(),
            'color' => $level->color(),
        ], self::cases());
    }
}
