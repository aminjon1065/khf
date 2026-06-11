<?php

namespace App\Http\Controllers\Public;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Subscribe to Web Push notifications.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
            'subscriber_token' => 'required|string',
            'topics' => 'nullable|array',
            'topics.*' => 'string',
            'region_id' => 'nullable|exists:regions,id',
            'locale' => 'required|string',
        ]);

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
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
            'subscriber_token' => 'required|string',
        ]);

        $subscriber = Subscriber::where('token', $validated['subscriber_token'])->first();

        if ($subscriber) {
            $subscriber->deletePushSubscription($validated['endpoint']);
        }

        return response()->json(['success' => true]);
    }
}
