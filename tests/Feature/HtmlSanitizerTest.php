<?php

use App\Support\HtmlSanitizer;

it('keeps safe markup and strips dangerous content', function () {
    $sanitizer = app(HtmlSanitizer::class);

    $clean = $sanitizer->clean(
        '<h2>Заголовок</h2><p>Текст</p><ul><li>Пункт</li></ul>'
        .'<script>alert(1)</script><img src="x" onerror="hack()">'
    );

    expect($clean)
        ->toContain('<h2>')
        ->toContain('<li>')
        ->not->toContain('<script')
        ->not->toContain('onerror');
});

it('passes null and empty values through untouched', function () {
    $sanitizer = app(HtmlSanitizer::class);

    expect($sanitizer->clean(null))->toBeNull()
        ->and($sanitizer->clean(''))->toBe('');
});
