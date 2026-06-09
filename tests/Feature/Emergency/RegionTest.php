<?php

use App\Enums\HazardLevel;
use App\Enums\IncidentType;
use App\Models\Region;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RegionSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(RegionSeeder::class);
});

it('seeds the four top-level regions with translations', function () {
    expect(Region::count())->toBe(4);

    $sughd = Region::where('code', 'SUGHD')->firstOrFail();

    expect($sughd->translation('ru')->name)->toBe('Согдийская область')
        ->and($sughd->translation('tj')->name)->toBe('Вилояти Суғд')
        ->and($sughd->translation('en')->name)->toBe('Sughd Region');
});

it('reseeds regions idempotently', function () {
    $this->seed(RegionSeeder::class);

    expect(Region::count())->toBe(4);
});

it('falls back to the fallback locale for a missing region translation', function () {
    config()->set('app.fallback_locale', 'tj');

    $dushanbe = Region::where('code', 'DUSHANBE')->firstOrFail();

    expect($dushanbe->translation('fr')->name)->toBe('Душанбе');
});

it('exposes incident types with colour and icon', function () {
    expect(IncidentType::values())->toHaveCount(7)
        ->and(IncidentType::Fire->label())->toBe('Пожар')
        ->and(IncidentType::Fire->icon())->toBe('flame');

    foreach (IncidentType::options() as $option) {
        expect($option['color'])->toStartWith('#');
    }
});

it('exposes hazard levels matching the design tokens', function () {
    expect(HazardLevel::values())->toBe(['normal', 'elevated', 'danger', 'critical'])
        ->and(HazardLevel::Critical->color())->toBe('#DC2626')
        ->and(HazardLevel::Critical->label())->toBe('Чрезвычайная опасность');
});
