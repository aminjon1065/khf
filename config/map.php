<?php

use App\Enums\HazardLevel;

/**
 * Static map overlays for the public portal (ТЗ §6.3).
 *
 * Risk-zone geometry is config-driven until a CMS model exists. Each ring is a closed polygon
 * ([lng, lat] pairs). Hazard values must match {@see HazardLevel}.
 */
return [
    'risk_zones' => [
        [
            'id' => 'sugd-flood',
            'hazard' => 'elevated',
            'name' => [
                'tj' => 'Минтақаи обгирӣ — вилояти Суғд',
                'ru' => 'Зона подтопления — Согдийская область',
                'en' => 'Flood-prone zone — Sughd region',
            ],
            'ring' => [
                [68.5, 39.5],
                [71.0, 39.5],
                [71.0, 41.5],
                [68.5, 41.5],
                [68.5, 39.5],
            ],
        ],
        [
            'id' => 'khatlon-mudflow',
            'hazard' => 'danger',
            'name' => [
                'tj' => 'Минтақаи сел — вилояти Хатлон',
                'ru' => 'Селеопасная зона — Хатлонская область',
                'en' => 'Mudflow-prone zone — Khatlon region',
            ],
            'ring' => [
                [67.5, 36.5],
                [70.5, 36.5],
                [70.5, 39.0],
                [67.5, 39.0],
                [67.5, 36.5],
            ],
        ],
        [
            'id' => 'gbao-avalanche',
            'hazard' => 'critical',
            'name' => [
                'tj' => 'Минтақаи барфрез — ВМКБ',
                'ru' => 'Лавиноопасная зона — ГБАО',
                'en' => 'Avalanche-prone zone — GBAO',
            ],
            'ring' => [
                [70.5, 37.5],
                [73.5, 37.5],
                [73.5, 39.5],
                [70.5, 39.5],
                [70.5, 37.5],
            ],
        ],
    ],
];
