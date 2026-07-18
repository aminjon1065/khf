<?php

use App\Models\Region;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function webPushPayload(string $endpoint, array $overrides = []): array
{
    return array_replace_recursive([
        'endpoint' => $endpoint,
        'keys' => [
            'auth' => 'auth-key',
            'p256dh' => 'p256dh-key',
        ],
        'topics' => ['alerts'],
        'region_id' => null,
        'locale' => 'tj',
    ], $overrides);
}

it('issues a server-owned token when subscribing to web push', function () {
    $region = Region::factory()->create();
    $endpoint = 'https://fcm.googleapis.com/fcm/send/new-subscriber';

    $response = $this->postJson(route('push.subscribe', ['locale' => 'tj']), webPushPayload($endpoint, [
        'region_id' => $region->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('success', true);

    $plainTextToken = $response->json('subscriber_token');
    $subscriber = Subscriber::findByPushToken($plainTextToken);

    expect($plainTextToken)->toBeString()->toHaveLength(64)
        ->and($subscriber)->not->toBeNull()
        ->and($subscriber->token)->not->toBe($plainTextToken)
        ->and($subscriber->push_token_hash)->toBe(hash('sha256', $plainTextToken))
        ->and($subscriber->email)->toBeNull()
        ->and($subscriber->region_id)->toBe($region->id)
        ->and($subscriber->topics)->toContain('alerts')
        ->and($subscriber->pushSubscriptions()->count())->toBe(1)
        ->and($subscriber->pushSubscriptions()->first()->endpoint)->toBe($endpoint);
});

it('updates an owned push subscription with its server token', function () {
    $endpoint = 'https://fcm.googleapis.com/fcm/send/update';
    $firstResponse = $this->postJson(
        route('push.subscribe', ['locale' => 'tj']),
        webPushPayload($endpoint),
    )->assertOk();
    $plainTextToken = $firstResponse->json('subscriber_token');

    $this->postJson(route('push.subscribe', ['locale' => 'tj']), webPushPayload($endpoint, [
        'subscriber_token' => $plainTextToken,
        'topics' => ['news'],
    ]))->assertOk()
        ->assertJsonPath('subscriber_token', $plainTextToken);

    $subscriber = Subscriber::findByPushToken($plainTextToken);

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->topics)->toBe(['news'])
        ->and(Subscriber::query()->count())->toBe(1)
        ->and($subscriber->pushSubscriptions()->count())->toBe(1);
});

it('prevents one subscriber from claiming another push endpoint', function () {
    $victimEndpoint = 'https://fcm.googleapis.com/fcm/send/victim';
    $attackerEndpoint = 'https://fcm.googleapis.com/fcm/send/attacker';
    $victimResponse = $this->postJson(
        route('push.subscribe', ['locale' => 'tj']),
        webPushPayload($victimEndpoint),
    )->assertOk();
    $attackerResponse = $this->postJson(
        route('push.subscribe', ['locale' => 'tj']),
        webPushPayload($attackerEndpoint),
    )->assertOk();

    $this->postJson(route('push.subscribe', ['locale' => 'tj']), webPushPayload($victimEndpoint, [
        'subscriber_token' => $attackerResponse->json('subscriber_token'),
    ]))->assertConflict();

    $victim = Subscriber::findByPushToken($victimResponse->json('subscriber_token'));
    $attacker = Subscriber::findByPushToken($attackerResponse->json('subscriber_token'));

    expect($victim->pushSubscriptions()->where('endpoint', $victimEndpoint)->exists())->toBeTrue()
        ->and($attacker->pushSubscriptions()->where('endpoint', $victimEndpoint)->exists())->toBeFalse();
});

it('only unsubscribes with the matching server token', function () {
    $endpoint = 'https://fcm.googleapis.com/fcm/send/unsubscribe';
    $response = $this->postJson(
        route('push.subscribe', ['locale' => 'tj']),
        webPushPayload($endpoint),
    )->assertOk();
    $plainTextToken = $response->json('subscriber_token');
    $subscriber = Subscriber::findByPushToken($plainTextToken);

    expect($subscriber->pushSubscriptions()->count())->toBe(1);

    $this->postJson(route('push.unsubscribe', ['locale' => 'tj']), [
        'endpoint' => $endpoint,
        'subscriber_token' => str_repeat('a', 64),
    ])->assertOk();

    expect($subscriber->pushSubscriptions()->count())->toBe(1);

    $this->postJson(route('push.unsubscribe', ['locale' => 'tj']), [
        'endpoint' => $endpoint,
        'subscriber_token' => $plainTextToken,
    ])->assertOk();

    expect($subscriber->pushSubscriptions()->count())->toBe(0);
});

it('backfills ownership hashes for existing push subscribers', function () {
    $migration = require database_path('migrations/2026_07_16_093815_add_push_token_hash_to_subscribers_table.php');
    $migration->down();

    $legacyToken = 'legacy-client-token-1234567890123456';
    $subscriber = Subscriber::factory()->create(['token' => $legacyToken]);
    $subscriber->updatePushSubscription('https://fcm.googleapis.com/fcm/send/legacy');

    $migration->up();

    expect($subscriber->fresh()->push_token_hash)->toBe(hash('sha256', $legacyToken))
        ->and(Subscriber::findByPushToken($legacyToken)?->is($subscriber))->toBeTrue();
});
