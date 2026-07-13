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
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppealController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Appeal::class);

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
        Gate::authorize('view', $appeal);

        $appeal->load(['assignee:id,name', 'media']);

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
                'deadline_at' => $appeal->deadline_at?->format('Y-m-d'),
                'created_at' => $appeal->created_at?->format('d.m.Y H:i'),
                'attachments' => $appeal->getMedia(Appeal::ATTACHMENTS_COLLECTION)->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'url' => route('admin.appeals.download-attachment', [$appeal, $media]),
                    'size' => $media->human_readable_size,
                ]),
            ],
            'statuses' => AppealStatus::options(),
            'staff' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                ->all(),
        ]);
    }

    public function downloadAttachment(Appeal $appeal, Media $media)
    {
        Gate::authorize('download', $appeal);

        if ($media->model_id !== $appeal->id || $media->model_type !== Appeal::class) {
            abort(404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    public function export(Request $request)
    {
        Gate::authorize('export', Appeal::class);

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
            ->get();

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="appeals-export-'.now()->format('Y-m-d').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($appeals) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fwrite($file, "\xEF\xBB\xBF");

            // Headers
            fputcsv($file, [
                'Номер', 'Категория', 'ФИО', 'Email', 'Телефон',
                'Тема', 'Статус', 'Исполнитель', 'Срок', 'Создано',
            ], ';');

            foreach ($appeals as $appeal) {
                fputcsv($file, [
                    $appeal->reference,
                    $appeal->category->label(),
                    $appeal->name,
                    $appeal->email,
                    $appeal->phone,
                    $appeal->subject,
                    $appeal->status->label(),
                    $appeal->assignee?->name ?? '',
                    $appeal->deadline_at?->format('d.m.Y') ?? '',
                    $appeal->created_at?->format('d.m.Y H:i') ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function update(UpdateAppealRequest $request, Appeal $appeal): RedirectResponse
    {
        $appeal->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Appeal updated.')]);

        return to_route('admin.appeals.show', $appeal);
    }

    public function destroy(Appeal $appeal): RedirectResponse
    {
        Gate::authorize('delete', $appeal);

        $appeal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Appeal deleted.')]);

        return to_route('admin.appeals.index');
    }
}
