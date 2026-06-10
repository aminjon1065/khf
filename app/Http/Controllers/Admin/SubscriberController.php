<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTopic;
use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriberController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = in_array((string) $request->string('status'), SubscriptionStatus::values(), true)
            ? (string) $request->string('status')
            : null;

        $topicLabels = collect(SubscriptionTopic::cases())
            ->mapWithKeys(fn (SubscriptionTopic $topic) => [$topic->value => $topic->label()]);

        $subscribers = Subscriber::query()
            ->with('region.translations')
            ->when($search !== '', fn (Builder $query) => $query->where('email', 'like', "%{$search}%"))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Subscriber $subscriber) => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status->value,
                'status_label' => $subscriber->status->label(),
                'topics' => collect($subscriber->topics ?? [])->map(fn (string $topic) => $topicLabels[$topic] ?? $topic)->all(),
                'created_at' => $subscriber->created_at?->format('d.m.Y'),
            ]);

        return Inertia::render('admin/subscribers/index', [
            'subscribers' => $subscribers,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => SubscriptionStatus::options(),
            'stats' => [
                'total' => Subscriber::count(),
                'confirmed' => Subscriber::where('status', SubscriptionStatus::Confirmed)->count(),
                'pending' => Subscriber::where('status', SubscriptionStatus::Pending)->count(),
            ],
        ]);
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscriber removed.')]);

        return to_route('admin.subscribers.index');
    }
}
