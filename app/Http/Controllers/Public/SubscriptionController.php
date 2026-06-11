<?php

namespace App\Http\Controllers\Public;

use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTopic;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriberRequest;
use App\Mail\SubscriptionConfirmation;
use App\Models\Region;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Public subscription form (ТЗ §6.4.3).
     */
    public function create(): Response
    {
        $locale = app()->getLocale();

        return Inertia::render('public/subscribe/index', [
            'topics' => SubscriptionTopic::options(),
            'regions' => Region::query()
                ->with('translations')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Region $region) => ['id' => $region->id, 'name' => $region->translation($locale)?->name ?? $region->code])
                ->all(),
            'status' => session('subscription_status'),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
        ]);
    }

    public function store(StoreSubscriberRequest $request): RedirectResponse
    {
        $locale = app()->getLocale();
        $data = $request->validated();

        $subscriber = Subscriber::updateOrCreate(
            ['email' => $data['email']],
            [
                'token' => Subscriber::generateToken(),
                'locale' => $locale,
                'status' => SubscriptionStatus::Pending,
                'topics' => $data['topics'],
                'region_id' => $data['region_id'] ?? null,
                'confirmed_at' => null,
                'consented_at' => now(),
            ],
        );

        Mail::to($subscriber->email)->locale($locale)->send(new SubscriptionConfirmation(
            $subscriber,
            route('subscriptions.confirm', ['locale' => $locale, 'token' => $subscriber->token]),
        ));

        return to_route('subscriptions.create', ['locale' => $locale])->with('subscription_status', 'pending');
    }

    public function confirm(string $locale, string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if ($subscriber === null) {
            return to_route('subscriptions.create', ['locale' => app()->getLocale()])->with('subscription_status', 'invalid');
        }

        $subscriber->update(['status' => SubscriptionStatus::Confirmed, 'confirmed_at' => now()]);

        return to_route('subscriptions.create', ['locale' => app()->getLocale()])->with('subscription_status', 'confirmed');
    }

    public function unsubscribe(string $locale, string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if ($subscriber !== null) {
            $subscriber->update(['status' => SubscriptionStatus::Unsubscribed]);
        }

        return to_route('subscriptions.create', ['locale' => app()->getLocale()])->with('subscription_status', 'unsubscribed');
    }
}
