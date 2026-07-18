<?php

namespace App\Http\Controllers\Public;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyPushSubscriptionRequest;
use App\Http\Requests\StorePushSubscriptionRequest;
use App\Models\Subscriber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    /**
     * Subscribe to Web Push notifications.
     */
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $plainTextToken = $validated['subscriber_token'] ?? null;
        $subscriber = $plainTextToken === null
            ? null
            : Subscriber::findByPushToken($plainTextToken);
        $pushSubscription = PushSubscription::findByEndpoint($validated['endpoint']);
        $issuedToken = null;

        if ($plainTextToken !== null && $subscriber === null) {
            return response()->json(['message' => 'Invalid push subscriber token.'], 401);
        }

        if ($pushSubscription !== null && ($subscriber === null || ! $subscriber->ownsPushSubscription($pushSubscription))) {
            return response()->json(['message' => 'Push subscription belongs to another subscriber.'], 409);
        }

        if ($plainTextToken === null) {
            $issuedToken = Subscriber::generatePushToken();
            $plainTextToken = $issuedToken['plainText'];
        }

        try {
            DB::transaction(function () use (
                &$subscriber,
                $issuedToken,
                $validated,
                $pushSubscription,
            ): void {
                if ($subscriber === null) {
                    if ($issuedToken === null) {
                        throw new \LogicException('A push token must be issued for a new subscriber.');
                    }

                    $subscriber = Subscriber::create([
                        'token' => Subscriber::generateToken(),
                        'push_token_hash' => $issuedToken['hash'],
                        'email' => null,
                        'locale' => $validated['locale'],
                        'status' => SubscriptionStatus::Confirmed,
                        'confirmed_at' => now(),
                        'consented_at' => now(),
                    ]);
                }

                $subscriber->update([
                    'topics' => $validated['topics'] ?? [],
                    'region_id' => $validated['region_id'] ?? null,
                    'locale' => $validated['locale'],
                ]);

                if ($pushSubscription !== null) {
                    $pushSubscription->forceFill([
                        'public_key' => $validated['keys']['p256dh'],
                        'auth_token' => $validated['keys']['auth'],
                    ])->save();

                    return;
                }

                $subscriber->pushSubscriptions()->create([
                    'endpoint' => $validated['endpoint'],
                    'public_key' => $validated['keys']['p256dh'],
                    'auth_token' => $validated['keys']['auth'],
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Push subscription is already registered.'], 409);
        }

        return response()->json([
            'success' => true,
            'subscriber_token' => $plainTextToken,
        ]);
    }

    /**
     * Unsubscribe from Web Push notifications.
     */
    public function destroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $subscriber = Subscriber::findByPushToken($validated['subscriber_token']);

        if ($subscriber) {
            $subscriber->deletePushSubscription($validated['endpoint']);
        }

        return response()->json(['success' => true]);
    }
}
