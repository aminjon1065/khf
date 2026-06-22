<?php

use App\Models\Region;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can subscribe to web push without email', function () {
    $region = Region::factory()->create();
    $token = 'test-uuid-token';

    $response = $this->postJson('/tj/push/subscribe', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
        'keys' => [
            'auth' => 'auth-key',
            'p256dh' => 'p256dh-key',
        ],
        'subscriber_token' => $token,
        'topics' => ['alerts'],
        'region_id' => $region->id,
        'locale' => 'tj',
    ]);

    $response->assertSuccessful();

    $subscriber = Subscriber::where('token', $token)->first();
    expect($subscriber)->not->toBeNull()
        ->and($subscriber->email)->toBeNull()
        ->and($subscriber->region_id)->toBe($region->id)
        ->and($subscriber->topics)->toContain('alerts');

    expect($subscriber->pushSubscriptions()->count())->toBe(1);

    $push = $subscriber->pushSubscriptions()->first();
    expect($push->endpoint)->toBe('https://fcm.googleapis.com/fcm/send/test');
});

it('can unsubscribe from web push', function () {
    $subscriber = Subscriber::factory()->create(['token' => 'test-uuid']);
    $subscriber->updatePushSubscription('https://fcm.googleapis.com/fcm/send/test');

    expect($subscriber->pushSubscriptions()->count())->toBe(1);

    $response = $this->postJson('/tj/push/unsubscribe', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
        'subscriber_token' => 'test-uuid',
    ]);

    $response->assertSuccessful();
    expect($subscriber->pushSubscriptions()->count())->toBe(0);
});
