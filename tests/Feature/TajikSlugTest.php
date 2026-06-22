<?php

use Illuminate\Support\Str;

it('transliterates tajik cyrillic characters correctly', function () {
    // Basic Tajik specific characters
    expect(Str::tajikSlug('Ғӣқӯҳҷёжхчшщэюяъь'))
        ->toBe('ghiquhjyozhkhchshshcheyuya');

    // Real world example
    expect(Str::tajikSlug('Ҷаласаи навбатии Ҳукумати Ҷумҳурии Тоҷикистон'))
        ->toBe('jalasai-navbatii-hukumati-jumhurii-tojikiston');

    // Mixed with English and Russian
    expect(Str::tajikSlug('Қарори №123 Update'))
        ->toBe('qarori-123-update');

    // With custom separator
    expect(Str::tajikSlug('Вазорати корҳои дохилӣ', '_'))
        ->toBe('vazorati_korhoi_dokhili');
});
