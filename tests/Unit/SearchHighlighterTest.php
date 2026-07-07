<?php

use App\Support\SearchHighlighter;

it('highlights query terms safely', function () {
    $highlighter = app(SearchHighlighter::class);

    $result = $highlighter->highlight('Землетрясение в Согдийской области', 'землетрясение');

    expect($result)
        ->toContain('<mark')
        ->toContain('Землетрясение')
        ->not->toContain('<script');
});

it('escapes html in the source text', function () {
    $highlighter = app(SearchHighlighter::class);

    $result = $highlighter->highlight('<script>alert(1)</script> тест', 'тест');

    expect($result)
        ->not->toContain('<script>')
        ->toContain('&lt;script&gt;');
});
