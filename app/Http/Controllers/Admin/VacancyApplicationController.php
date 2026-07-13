<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVacancyApplicationRequest;
use App\Models\User;
use App\Models\VacancyApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VacancyApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', VacancyApplication::class);

        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $status = in_array((string) $request->string('status'), AppealStatus::values(), true)
            ? (string) $request->string('status')
            : null;

        $applications = VacancyApplication::query()
            ->with(['assignee:id,name', 'vacancy.translations'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (VacancyApplication $application) => [
                'id' => $application->id,
                'reference' => $application->reference,
                'full_name' => $application->full_name,
                'vacancy' => $application->vacancy?->translation($locale)?->title,
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'assignee' => $application->assignee?->name,
                'created_at' => $application->created_at?->format('d.m.Y'),
            ]);

        return Inertia::render('admin/vacancy-applications/index', [
            'applications' => $applications,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => AppealStatus::options(),
        ]);
    }

    public function show(VacancyApplication $application): Response
    {
        Gate::authorize('view', $application);

        $locale = app()->getLocale();
        $application->load(['assignee:id,name', 'vacancy.translations', 'media']);
        $resume = $application->getFirstMedia(VacancyApplication::RESUME_COLLECTION);

        return Inertia::render('admin/vacancy-applications/show', [
            'application' => [
                'id' => $application->id,
                'reference' => $application->reference,
                'vacancy' => $application->vacancy?->translation($locale)?->title,
                'full_name' => $application->full_name,
                'email' => $application->email,
                'phone' => $application->phone,
                'cover_letter' => $application->cover_letter,
                'status' => $application->status->value,
                'assigned_to' => $application->assigned_to,
                'internal_note' => $application->internal_note,
                'created_at' => $application->created_at?->format('d.m.Y H:i'),
                'resume' => $resume === null ? null : [
                    'name' => $resume->file_name,
                    'size' => $resume->humanReadableSize,
                    'url' => route('admin.vacancy-applications.resume', $application),
                ],
            ],
            'statuses' => AppealStatus::options(),
            'staff' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                ->all(),
        ]);
    }

    public function update(UpdateVacancyApplicationRequest $request, VacancyApplication $application): RedirectResponse
    {
        $application->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application updated.')]);

        return to_route('admin.vacancy-applications.show', $application);
    }

    public function destroy(VacancyApplication $application): RedirectResponse
    {
        Gate::authorize('delete', $application);

        $application->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application deleted.')]);

        return to_route('admin.vacancy-applications.index');
    }

    /**
     * Controlled download of the applicant's questionnaire/CV — personal data on the private disk,
     * served only to staff with permission (ТЗ §12.5).
     */
    public function downloadResume(VacancyApplication $application): BinaryFileResponse
    {
        Gate::authorize('download', $application);

        $resume = $application->getFirstMedia(VacancyApplication::RESUME_COLLECTION);

        abort_if($resume === null, 404);

        return response()->download($resume->getPath(), $resume->file_name);
    }
}
