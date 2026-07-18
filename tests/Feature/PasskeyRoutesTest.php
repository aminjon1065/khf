<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::passkeys());
});

it('provides passkey login options to guests', function () {
    $this->getJson(route('passkey.login-options'))
        ->assertOk()
        ->assertJsonStructure(['options' => ['challenge']]);
});

it('protects passkey management routes with authentication', function () {
    $this->get(route('passkey.registration-options'))
        ->assertRedirect(route('login'));

    $this->delete(route('passkey.destroy', ['passkey' => 1]))
        ->assertRedirect(route('login'));
});

it('requires recent password confirmation before passkey registration', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('passkey.registration-options'))
        ->assertRedirect(route('password.confirm'));
});

it('rate limits passkey login option requests', function () {
    $url = route('passkey.login-options', [
        'credential' => ['id' => 'fixed-test-credential'],
    ]);

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->getJson($url)->assertOk();
    }

    $this->getJson($url)->assertTooManyRequests();
});
