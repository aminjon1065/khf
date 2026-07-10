<?php

use App\Cms\Blueprint\BlueprintParser;
use App\Cms\Blueprint\BlueprintRepository;
use App\Cms\Blueprint\BlueprintSerializer;
use App\Cms\Blueprint\BlueprintValidator;
use App\Enums\AlertStatus;
use App\Enums\DocumentType;
use App\Enums\EmploymentType;
use App\Enums\GuideAudience;
use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\PollType;
use App\Enums\PostType;
use App\Enums\ServiceCategory;
use App\Enums\TenderType;
use App\Models\Language;
use Database\Seeders\LanguageSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

it('parses a blueprint yaml file', function () {
    $blueprint = app(BlueprintRepository::class)->find('post.default');

    expect($blueprint->handle)->toBe('post.default')
        ->and($blueprint->title)->toBe('Новость')
        ->and($blueprint->section('main'))->not->toBeNull()
        ->and($blueprint->field('title')?->isLocalizable())->toBeTrue()
        ->and($blueprint->field('type')?->type)->toBe('select');
});

it('serializes blueprint for the inertia form', function () {
    $array = app(BlueprintRepository::class)->find('page.default')->toArray();

    expect($array)->toHaveKeys(['handle', 'title', 'sections'])
        ->and($array['sections']['sidebar']['fields'])->not->toBeEmpty()
        ->and(collect($array['sections']['main']['fields'])->pluck('handle')->all())
        ->toContain('title', 'blocks');
});

it('generates validation rules from a blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('post.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [
            'slug' => [
                'table' => 'post_translations',
                'column' => 'slug',
                'foreign_key' => 'post_id',
                'exclude_id' => null,
            ],
        ],
        ['type' => PostType::values()],
    );

    expect($rules)->toHaveKey('type')
        ->and($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('translations.ru.slug')
        ->and($rules)->toHaveKey('cover')
        ->and($rules)->toHaveKey('tag_ids');
});

it('parses blueprint via parser directly', function () {
    $yaml = <<<'YAML'
title: Test
sections:
  main:
    fields:
      - handle: title
        type: text
        localizable: true
YAML;

    $blueprint = app(BlueprintParser::class)->parse('test.default', $yaml);

    expect($blueprint->field('title')?->display())->toBe('Title');
});

it('throws when blueprint file is missing', function () {
    app(BlueprintRepository::class)->find('missing.default');
})->throws(InvalidArgumentException::class);

it('generates validation rules for replicator fields in globals', function () {
    $blueprint = app(BlueprintRepository::class)->find('footer.default');
    $rules = app(BlueprintValidator::class)->flatRules($blueprint);

    expect($rules)->toHaveKey('fields.resource_links')
        ->and($rules)->toHaveKey('fields.resource_links.*.label')
        ->and($rules)->toHaveKey('fields.resource_links.*.url')
        ->and($blueprint->field('resource_links')?->type)->toBe('replicator')
        ->and($blueprint->field('resource_links')?->subFields())->toHaveCount(2);
});

it('generates validation rules from the document blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('document.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [],
        ['type' => DocumentType::values()],
    );

    expect($rules)->toHaveKey('translations.tj.name')
        ->and($rules)->toHaveKey('type')
        ->and($rules)->toHaveKey('files')
        ->and($rules)->toHaveKey('files.*');
});

it('generates validation rules from the guide blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('guide.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [
            'slug' => [
                'table' => 'guide_translations',
                'column' => 'slug',
                'foreign_key' => 'guide_id',
                'exclude_id' => null,
            ],
        ],
        [
            'audience' => GuideAudience::values(),
            'hazard_type' => array_merge([''], IncidentType::values()),
        ],
    );

    expect($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('translations.ru.slug')
        ->and($rules)->toHaveKey('audience')
        ->and($rules)->toHaveKey('files.*');
});

it('generates validation rules from the faq blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('faq.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
    );

    expect($rules)->toHaveKey('translations.tj.question')
        ->and($rules)->toHaveKey('translations.tj.answer')
        ->and($rules)->toHaveKey('sort_order');
});

it('generates validation rules from the statistic blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('statistic.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
    );

    expect($rules)->toHaveKey('translations.tj.label')
        ->and($rules)->toHaveKey('value')
        ->and($rules)->toHaveKey('year')
        ->and($rules['year'])->toContain('min:1900');
});

it('generates validation rules from the gallery blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('gallery.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [
            'slug' => [
                'table' => 'gallery_translations',
                'column' => 'slug',
                'foreign_key' => 'gallery_id',
                'exclude_id' => null,
            ],
        ],
    );

    expect($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('translations.ru.slug')
        ->and($rules)->toHaveKey('photos.*')
        ->and($rules)->toHaveKey('remove_photos.*');
});

it('generates validation rules from the leader blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('leader.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
    );

    expect($rules)->toHaveKey('translations.tj.full_name')
        ->and($rules)->toHaveKey('translations.tj.position')
        ->and($rules)->toHaveKey('photo')
        ->and($rules)->toHaveKey('email')
        ->and($rules['email'])->toContain('email');
});

it('generates validation rules from the gov_service blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('gov_service.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [],
        ['category' => ServiceCategory::values()],
    );

    expect($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('category')
        ->and($rules)->toHaveKey('is_online');
});

it('generates validation rules from the poll blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('poll.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [],
        ['type' => PollType::values()],
    );

    expect($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('type')
        ->and($rules)->toHaveKey('options');
});

it('generates validation rules from the vacancy blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('vacancy.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [
            'slug' => [
                'table' => 'vacancy_translations',
                'column' => 'slug',
                'foreign_key' => 'vacancy_id',
                'exclude_id' => null,
            ],
        ],
        ['employment_type' => EmploymentType::values()],
    );

    expect($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('employment_type')
        ->and($rules)->toHaveKey('positions_count')
        ->and($rules['positions_count'])->toContain('min:1');
});

it('generates validation rules from the tender blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('tender.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [
            'slug' => [
                'table' => 'tender_translations',
                'column' => 'slug',
                'foreign_key' => 'tender_id',
                'exclude_id' => null,
            ],
        ],
        ['type' => TenderType::values()],
    );

    expect($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('type')
        ->and($rules)->toHaveKey('lots_count')
        ->and($rules)->toHaveKey('budget');
});

it('generates validation rules from the incident blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('incident.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [],
        [
            'type' => IncidentType::values(),
            'hazard_level' => HazardLevel::values(),
            'status' => IncidentStatus::values(),
        ],
    );

    expect($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('type')
        ->and($rules)->toHaveKey('region_id')
        ->and($rules)->toHaveKey('latitude');
});

it('generates validation rules from the alert blueprint', function () {
    $blueprint = app(BlueprintRepository::class)->find('alert.default');
    $rules = app(BlueprintValidator::class)->rules(
        $blueprint,
        Language::codes(),
        Language::defaultCode(),
        [],
        [
            'hazard_level' => HazardLevel::values(),
            'status' => AlertStatus::values(),
        ],
    );

    expect($rules)->toHaveKey('translations.tj.title')
        ->and($rules)->toHaveKey('translations.tj.body')
        ->and($rules)->toHaveKey('hazard_level')
        ->and($rules)->toHaveKey('is_dismissible');
});

it('lists all blueprint yaml files', function () {
    $items = app(BlueprintRepository::class)->all();

    expect($items)->toHaveCount(19)
        ->and(collect($items)->pluck('handle')->all())
        ->toContain('post.default', 'page.default', 'footer.default', 'document.default', 'incident.default');
});

it('has a blueprint yaml file for every configured content type and global', function () {
    $repository = app(BlueprintRepository::class);

    $references = collect(config('cms.content_types'))
        ->pluck('blueprint')
        ->merge(collect(config('cms.globals'))->pluck('blueprint'))
        ->unique()
        ->values();

    foreach ($references as $reference) {
        expect($repository->exists($reference))
            ->toBeTrue("Blueprint [{$reference}] is missing from resources/blueprints/");
    }
});

it('writes blueprint yaml to disk', function () {
    $repository = app(BlueprintRepository::class);
    $path = resource_path('blueprints/social/default.yaml');
    $original = file_get_contents($path);
    $updated = str_replace('title: Социальные сети', 'title: Соцсети портала', $original);

    try {
        $repository->write('social', 'default', $updated);

        expect(file_get_contents($path))->toContain('title: Соцсети портала');
    } finally {
        file_put_contents($path, $original);
    }
});

it('serializes builder schema to yaml', function () {
    $schema = app(BlueprintRepository::class)->find('footer.default')->toArray();
    $yaml = app(BlueprintSerializer::class)->toYaml('footer.default', $schema);

    expect($yaml)
        ->toContain('title:')
        ->toContain('resource_links')
        ->and(app(BlueprintParser::class)->parse('footer.default', $yaml)->title)
        ->toBe($schema['title']);
});
