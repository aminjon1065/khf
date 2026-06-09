<?php

test('the root url redirects to the resolved localized homepage', function () {
    $this->withSession(['locale' => 'tj'])
        ->get(route('home'))
        ->assertRedirect(route('welcome', ['locale' => 'tj']));
});

test('a localized homepage returns a successful response', function () {
    $this->get(route('welcome', ['locale' => 'tj']))->assertOk();
    $this->get(route('welcome', ['locale' => 'ru']))->assertOk();
});

test('an unsupported locale prefix is not found', function () {
    $this->get('/xx')->assertNotFound();
});
