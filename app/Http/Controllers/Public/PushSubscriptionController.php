<?php

namespace App\Http\Controllers\Public;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyPushSubscriptionRequest;
use App\Http\Requests\StorePushSubscriptionRequest;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    /**
     * Subscribe to Web Push notifications.
     */
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Find or create subscriber by token
        $subscriber = Subscriber::firstOrCreate(
            ['token' => $validated['subscriber_token']],
            [
                'email' => null,
                'locale' => $validated['locale'],
                'status' => SubscriptionStatus::Confirmed,
                'confirmed_at' => now(),
                'consented_at' => now(),
            ]
        );

        // Update preferences
        $subscriber->update([
            'topics' => $validated['topics'] ?? [],
            'region_id' => $validated['region_id'] ?? null,
            'locale' => $validated['locale'],
        ]);

        // Update push subscription
        $subscriber->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth']
        );

        return response()->json(['success' => true]);
    }

    /**
     * Unsubscribe from Web Push notifications.
     */
    public function destroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $subscriber = Subscriber::where('token', $validated['subscriber_token'])->first();

        if ($subscriber) {
            $subscriber->deletePushSubscription($validated['endpoint']);
        }

        return response()->json(['success' => true]);
    }
}
