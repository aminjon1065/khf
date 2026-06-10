<?php

use Database\Seeders\LanguageSeeder;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('emits canonical and hreflang alternates on localized public pages', function () {
    $response = $this->get(route('news.index', ['locale' => 'ru']))->assertOk();

    $response->assertSee('<link rel="canonical" href="'.url('ru/news').'">', false);
    $response->assertSee('<link rel="alternate" hreflang="tg" href="'.url('tj/news').'">', false);
    $response->assertSee('<link rel="alternate" hreflang="ru" href="'.url('ru/news').'">', false);
    $response->assertSee('<link rel="alternate" hreflang="en" href="'.url('en/news').'">', false);
    $response->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('tj/news').'">', false);
});

it('uses a valid BCP-47 tag in the html lang attribute', function () {
    $this->get(route('welcome', ['locale' => 'tj']))
        ->assertOk()
        ->assertSee('<html lang="tg"', false);
});

it('does not emit alternates on non-localized routes', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('rel="alternate"', false);
});
