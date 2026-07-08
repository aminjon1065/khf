<?php

use App\Enums\ContentStatus;
use App\Enums\ServiceCategory;
use App\Models\GovService;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

function createPublishedService(): GovService
{
    $service = GovService::factory()->create([
        'status' => ContentStatus::Published,
        'category' => ServiceCategory::Registration,
        'is_online' => true,
        'external_url' => 'https://egov.tj/service',
        'processing_time' => '10 дней',
        'fee' => 'Бесплатно',
    ]);

    $service->upsertTranslations([
        'ru' => [
            'title' => 'Регистрация туристической группы',
            'slug' => 'registraciya-turgruppy-ru',
            'summary' => 'Онлайн-регистрация маршрута',
            'description' => '<p>Порядок подачи заявки</p>',
            'eligibility' => '<p>Организаторы туров</p>',
            'required_documents' => '<p>Список документов</p>',
        ],
    ]);

    return $service;
}

it('renders the public services catalogue', function () {
    createPublishedService();

    $this->get(route('services.index', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/services/index')
            ->has('services', 1)
            ->where('services.0.title', 'Регистрация туристической группы'));
});

it('filters services by category', function () {
    createPublishedService();
    GovService::factory()->create([
        'status' => ContentStatus::Published,
        'category' => ServiceCategory::Consultation,
    ])->upsertTranslations([
        'ru' => [
            'title' => 'Консультация',
            'slug' => 'konsultaciya-ru',
            'summary' => null,
            'description' => null,
            'eligibility' => null,
            'required_documents' => null,
        ],
    ]);

    $this->get(route('services.index', ['locale' => 'ru', 'category' => ServiceCategory::Registration->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('services', 1));
});

it('renders a service detail page', function () {
    createPublishedService();

    $this->get(route('services.show', ['locale' => 'ru', 'slug' => 'registraciya-turgruppy-ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/services/show')
            ->where('service.title', 'Регистрация туристической группы')
            ->where('service.is_online', true)
            ->where('service.external_url', 'https://egov.tj/service'));
});
