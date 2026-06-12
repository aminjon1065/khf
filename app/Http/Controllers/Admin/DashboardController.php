<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AlertStatus;
use App\Enums\AppealStatus;
use App\Enums\ContentStatus;
use App\Enums\IncidentStatus;
use App\Enums\Permission;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Appeal;
use App\Models\Document;
use App\Models\Guide;
use App\Models\Incident;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\TouristGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * The CMS dashboard — an operational overview for staff (ТЗ §7): what needs attention now
     * (new appeals, active alerts/incidents), key counts per module, and recent inbox activity.
     * Every block is gated by the viewer's permissions so a moderator never sees system-only data.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $can = fn (Permission $permission): bool => (bool) $user?->can($permission->value);
        $locale = app()->getLocale();

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'appeals' => $can(Permission::ViewAppeals) ? $this->appealStats() : null,
                'incidents' => $can(Permission::ViewIncidents) ? $this->incidentStats() : null,
                'alerts' => $can(Permission::ViewAlerts) ? $this->alertStats() : null,
                'touristGroups' => $can(Permission::ViewTouristGroups) ? $this->touristGroupStats() : null,
                'subscribers' => $can(Permission::ViewSubscribers) ? $this->subscriberStats() : null,
                'content' => $can(Permission::ViewPosts) ? $this->contentStats() : null,
                'system' => $can(Permission::ViewUsers) ? $this->systemStats() : null,
            ],
            'recentAppeals' => $can(Permission::ViewAppeals) ? $this->recentAppeals() : [],
            'recentIncidents' => $can(Permission::ViewIncidents) ? $this->recentIncidents($locale) : [],
        ]);
    }

    /**
     * @return array{new: int, in_progress: int, total: int}
     */
    private function appealStats(): array
    {
        $byStatus = $this->countByStatus(Appeal::query());

        return [
            'new' => (int) ($byStatus[AppealStatus::New->value] ?? 0),
            'in_progress' => (int) ($byStatus[AppealStatus::InProgress->value] ?? 0),
            'total' => (int) $byStatus->sum(),
        ];
    }

    /**
     * @return array{active: int, controlled: int, total: int}
     */
    private function incidentStats(): array
    {
        $byStatus = $this->countByStatus(Incident::query());

        return [
            'active' => (int) ($byStatus[IncidentStatus::Active->value] ?? 0),
            'controlled' => (int) ($byStatus[IncidentStatus::Controlled->value] ?? 0),
            'total' => (int) $byStatus->sum(),
        ];
    }

    /**
     * @return array{active: int, total: int}
     */
    private function alertStats(): array
    {
        return [
            'active' => Alert::query()
                ->where('status', AlertStatus::Published)
                ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->count(),
            'total' => Alert::query()->count(),
        ];
    }

    /**
     * @return array{pending: int, on_route: int, total: int}
     */
    private function touristGroupStats(): array
    {
        return [
            'pending' => TouristGroup::query()->where('status', AppealStatus::New)->count(),
            'on_route' => TouristGroup::query()
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->count(),
            'total' => TouristGroup::query()->count(),
        ];
    }

    /**
     * @return array{confirmed: int, pending: int}
     */
    private function subscriberStats(): array
    {
        $byStatus = $this->countByStatus(Subscriber::query());

        return [
            'confirmed' => (int) ($byStatus[SubscriptionStatus::Confirmed->value] ?? 0),
            'pending' => (int) ($byStatus[SubscriptionStatus::Pending->value] ?? 0),
        ];
    }

    /**
     * @return array{posts_published: int, posts_total: int, pages: int, documents: int, guides: int}
     */
    private function contentStats(): array
    {
        $posts = $this->countByStatus(Post::query());

        return [
            'posts_published' => (int) ($posts[ContentStatus::Published->value] ?? 0),
            'posts_total' => (int) $posts->sum(),
            'pages' => Page::query()->count(),
            'documents' => Document::query()->count(),
            'guides' => Guide::query()->count(),
        ];
    }

    /**
     * @return array{users: int, languages: int, roles: int}
     */
    private function systemStats(): array
    {
        return [
            'users' => User::query()->count(),
            'languages' => Language::query()->where('is_active', true)->count(),
            'roles' => Role::query()->count(),
        ];
    }

    /**
     * One grouped query instead of one COUNT per status.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return \Illuminate\Support\Collection<string, int>
     */
    private function countByStatus(Builder $query): \Illuminate\Support\Collection
    {
        return $query->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    /**
     * @return list<array{id: int, reference: string, subject: string, status: string, status_label: string, created_at: string|null}>
     */
    private function recentAppeals(): array
    {
        return Appeal::query()
            ->latest()
            ->limit(6)
            ->get(['id', 'reference', 'subject', 'status', 'created_at'])
            ->map(fn (Appeal $appeal): array => [
                'id' => $appeal->id,
                'reference' => $appeal->reference,
                'subject' => $appeal->subject,
                'status' => $appeal->status->value,
                'status_label' => $appeal->status->label(),
                'created_at' => $appeal->created_at?->format('d.m.Y H:i'),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, title: string|null, status: string, status_label: string, hazard_level: string, occurred_at: string|null}>
     */
    private function recentIncidents(string $locale): array
    {
        return Incident::query()
            ->with('translations')
            ->latest('occurred_at')
            ->limit(6)
            ->get()
            ->map(fn (Incident $incident): array => [
                'id' => $incident->id,
                'title' => $incident->translation($locale)?->title,
                'status' => $incident->status->value,
                'status_label' => $incident->status->label(),
                'hazard_level' => $incident->hazard_level->value,
                'occurred_at' => $incident->occurred_at?->format('d.m.Y H:i'),
            ])
            ->all();
    }
}
