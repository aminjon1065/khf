<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTouristGroupRequest;
use App\Models\TouristGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TouristGroupController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $status = in_array((string) $request->string('status'), AppealStatus::values(), true)
            ? (string) $request->string('status')
            : null;

        $groups = TouristGroup::query()
            ->with(['assignee:id,name', 'region.translations'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('leader_name', 'like', "%{$search}%")))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (TouristGroup $group) => [
                'id' => $group->id,
                'reference' => $group->reference,
                'leader_name' => $group->leader_name,
                'participants_count' => $group->participants_count,
                'region' => $group->region?->translation($locale)?->name,
                'status' => $group->status->value,
                'status_label' => $group->status->label(),
                'assignee' => $group->assignee?->name,
                'start_date' => $group->start_date?->format('d.m.Y'),
            ]);

        return Inertia::render('admin/tourist-groups/index', [
            'groups' => $groups,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => AppealStatus::options(),
        ]);
    }

    public function show(TouristGroup $touristGroup): Response
    {
        $locale = app()->getLocale();
        $touristGroup->load(['assignee:id,name', 'region.translations']);

        return Inertia::render('admin/tourist-groups/show', [
            'group' => [
                'id' => $touristGroup->id,
                'reference' => $touristGroup->reference,
                'leader_name' => $touristGroup->leader_name,
                'leader_phone' => $touristGroup->leader_phone,
                'leader_email' => $touristGroup->leader_email,
                'participants_count' => $touristGroup->participants_count,
                'route' => $touristGroup->route,
                'equipment' => $touristGroup->equipment,
                'region' => $touristGroup->region?->translation($locale)?->name,
                'start_date' => $touristGroup->start_date?->format('d.m.Y'),
                'end_date' => $touristGroup->end_date?->format('d.m.Y'),
                'start_latitude' => $touristGroup->start_latitude,
                'start_longitude' => $touristGroup->start_longitude,
                'status' => $touristGroup->status->value,
                'assigned_to' => $touristGroup->assigned_to,
                'internal_note' => $touristGroup->internal_note,
            ],
            'statuses' => AppealStatus::options(),
            'staff' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                ->all(),
        ]);
    }

    public function update(UpdateTouristGroupRequest $request, TouristGroup $touristGroup): RedirectResponse
    {
        $touristGroup->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application updated.')]);

        return to_route('admin.tourist-groups.show', $touristGroup);
    }

    public function destroy(TouristGroup $touristGroup): RedirectResponse
    {
        $touristGroup->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application deleted.')]);

        return to_route('admin.tourist-groups.index');
    }
}
