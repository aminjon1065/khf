<?php

use App\Enums\AlertStatus;
use App\Models\Alert;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the public detail page for a published alert', function () {
    $alert = Alert::factory()->create(['status' => AlertStatus::Published]);
    $alert->upsertTranslations(['tj' => ['title' => 'Обу ҳаво', 'body' => 'Огоҳӣ']]);

    $this->get(route('alerts.show', ['locale' => 'tj', 'alert' => $alert->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/alerts/show')
            ->where('alert.title', 'Обу ҳаво')
            ->where('alert.is_active', true));
});

it('marks an expired but still-published alert as inactive', function () {
    $alert = Alert::factory()->create([
        'status' => AlertStatus::Published,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);
    $alert->upsertTranslations(['tj' => ['title' => 'Кӯҳна', 'body' => '']]);

    $this->get(route('alerts.show', ['locale' => 'tj', 'alert' => $alert->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('alert.is_active', false));
});

it('404s for a draft alert', function () {
    $alert = Alert::factory()->draft()->create();

    $this->get(route('alerts.show', ['locale' => 'tj', 'alert' => $alert->id]))
        ->assertNotFound();
});

it('404s for a cancelled alert', function () {
    $alert = Alert::factory()->create(['status' => AlertStatus::Cancelled]);

    $this->get(route('alerts.show', ['locale' => 'tj', 'alert' => $alert->id]))
        ->assertNotFound();
});
