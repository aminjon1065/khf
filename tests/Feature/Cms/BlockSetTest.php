<?php

use App\Cms\BlockSet\BlockSetParser;
use App\Cms\BlockSet\BlockSetRepository;
use App\Support\PublicBlockTypes;

it('parses block defaults from yaml', function () {
    $yaml = <<<'YAML'
blocks:
  hero:
    label: Hero
    defaults:
      title: ''
      subtitle: ''
YAML;

    $blockSet = app(BlockSetParser::class)->parse('test', $yaml);

    expect($blockSet->find('hero')?->defaults)->toBe([
        'title' => '',
        'subtitle' => '',
    ]);
});

it('throws when block set file is missing', function () {
    app(BlockSetRepository::class)->find('missing');
})->throws(InvalidArgumentException::class);

it('keeps the page block set aligned with the public renderer registry', function () {
    $blockSet = app(BlockSetRepository::class)->find('page');

    expect(collect($blockSet->blocks)->pluck('type')->all())
        ->toEqual(PublicBlockTypes::PAGE);
});

it('loads specialised block sets for homepage about and landing', function () {
    $homepage = app(BlockSetRepository::class)->find('homepage');
    $about = app(BlockSetRepository::class)->find('about');
    $landing = app(BlockSetRepository::class)->find('landing');

    expect(collect($homepage->blocks)->pluck('type')->all())
        ->toEqual(['cta', 'news_list', 'map_widget'])
        ->and(collect($about->blocks)->pluck('type')->all())
        ->toEqual(['text', 'accordion', 'contacts'])
        ->and(collect($landing->blocks)->pluck('type')->all())
        ->toEqual(['text', 'image_gallery', 'cta', 'table']);
});
