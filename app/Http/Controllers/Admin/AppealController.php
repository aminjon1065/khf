<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppealRequest;
use App\Models\Appeal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppealController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = in_array((string) $request->string('status'), AppealStatus::values(), true)
            ? (string) $request->string('status')
            : null;

        $appeals = Appeal::query()
            ->with('assignee:id,name')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Appeal $appeal) => [
                'id' => $appeal->id,
                'reference' => $appeal->reference,
                'subject' => $appeal->subject,
                'category_label' => $appeal->category->label(),
                'status' => $appeal->status->value,
                'status_label' => $appeal->status->label(),
                'assignee' => $appeal->assignee?->name,
                'created_at' => $appeal->created_at?->format('d.m.Y'),
            ]);

        return Inertia::render('admin/appeals/index', [
            'appeals' => $appeals,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => AppealStatus::options(),
        ]);
    }

    public function show(Appeal $appeal): Response
    {
        $appeal->load('assignee:id,name');

        return Inertia::render('admin/appeals/show', [
            'appeal' => [
                'id' => $appeal->id,
                'reference' => $appeal->reference,
                'category_label' => $appeal->category->label(),
                'name' => $appeal->name,
                'email' => $appeal->email,
                'phone' => $appeal->phone,
                'subject' => $appeal->subject,
                'message' => $appeal->message,
                'status' => $appeal->status->value,
                'assigned_to' => $appeal->assigned_to,
                'internal_note' => $appeal->internal_note,
                'created_at' => $appeal->created_at?->format('d.m.Y H:i'),
            ],
            'statuses' => AppealStatus::options(),
            'staff' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                ->all(),
        ]);
    }

    public function update(UpdateAppealRequest $request, Appeal $appeal): RedirectResponse
    {
        $appeal->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Appeal updated.')]);

        return to_route('admin.appeals.show', $appeal);
    }

    public function destroy(Appeal $appeal): RedirectResponse
    {
        $appeal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Appeal deleted.')]);

        return to_route('admin.appeals.index');
    }
}
