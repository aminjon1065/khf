<?php

namespace App\Enums;

/**
 * Types of emergency events (ТЗ §6.3, Приложение Б). A fixed reference set; multilingual display
 * names are provided via interface translations (Phase 13). Each type carries a marker colour and a
 * lucide icon name for the map and badges.
 */
enum IncidentType: string
{
    case Earthquake = 'earthquake';
    case Mudflow = 'mudflow';
    case Flood = 'flood';
    case Avalanche = 'avalanche';
    case Landslide = 'landslide';
    case Fire = 'fire';
    case Glof = 'glof';

    /**
     * Russian label for the CMS (§7.1).
     */
    public function label(): string
    {
        return match ($this) {
            self::Earthquake => 'Землетрясение',
            self::Mudflow => 'Сель и паводок',
            self::Flood => 'Наводнение',
            self::Avalanche => 'Лавина',
            self::Landslide => 'Оползень',
            self::Fire => 'Пожар',
            self::Glof => 'Прорыв ледниковых озёр',
        };
    }

    /**
     * Marker colour (hex) for the map and badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Earthquake => '#B45309',
            self::Mudflow => '#0E7490',
            self::Flood => '#2563EB',
            self::Avalanche => '#0891B2',
            self::Landslide => '#92400E',
            self::Fire => '#DC2626',
            self::Glof => '#7C3AED',
        };
    }

    /**
     * lucide-react icon name used on the client.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Earthquake => 'activity',
            self::Mudflow => 'waves',
            self::Flood => 'droplets',
            self::Avalanche => 'mountain-snow',
            self::Landslide => 'mountain',
            self::Fire => 'flame',
            self::Glof => 'snowflake',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string, color: string, icon: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'color' => $type->color(),
            'icon' => $type->icon(),
        ], self::cases());
    }
}
