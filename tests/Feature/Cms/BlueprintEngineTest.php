<?php

use App\Cms\Blueprint\BlueprintParser;
use App\Cms\Blueprint\BlueprintRepository;
use App\Cms\Blueprint\BlueprintSerializer;
use App\Cms\Blueprint\BlueprintValidator;
use App\Enums\PostType;
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

it('lists all blueprint yaml files', function () {
    $items = app(BlueprintRepository::class)->all();

    expect($items)->toHaveCount(6)
        ->and(collect($items)->pluck('handle')->all())
        ->toContain('post.default', 'page.default', 'footer.default');
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
