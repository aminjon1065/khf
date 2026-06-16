<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTenderBidRequest;
use App\Models\TenderBid;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TenderBidController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $status = in_array((string) $request->string('status'), AppealStatus::values(), true)
            ? (string) $request->string('status')
            : null;

        $bids = TenderBid::query()
            ->with(['assignee:id,name', 'tender.translations'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (TenderBid $bid) => [
                'id' => $bid->id,
                'reference' => $bid->reference,
                'company_name' => $bid->company_name,
                'tender' => $bid->tender?->translation($locale)?->title,
                'status' => $bid->status->value,
                'status_label' => $bid->status->label(),
                'assignee' => $bid->assignee?->name,
                'created_at' => $bid->created_at?->format('d.m.Y'),
            ]);

        return Inertia::render('admin/tender-bids/index', [
            'bids' => $bids,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => AppealStatus::options(),
        ]);
    }

    public function show(TenderBid $bid): Response
    {
        $locale = app()->getLocale();
        $bid->load(['assignee:id,name', 'tender.translations', 'media']);
        $document = $bid->getFirstMedia(TenderBid::DOCUMENT_COLLECTION);

        return Inertia::render('admin/tender-bids/show', [
            'bid' => [
                'id' => $bid->id,
                'reference' => $bid->reference,
                'tender' => $bid->tender?->translation($locale)?->title,
                'company_name' => $bid->company_name,
                'contact_name' => $bid->contact_name,
                'email' => $bid->email,
                'phone' => $bid->phone,
                'proposal' => $bid->proposal,
                'status' => $bid->status->value,
                'assigned_to' => $bid->assigned_to,
                'internal_note' => $bid->internal_note,
                'created_at' => $bid->created_at?->format('d.m.Y H:i'),
                'document' => $document === null ? null : [
                    'name' => $document->file_name,
                    'size' => $document->humanReadableSize,
                    'url' => route('admin.tender-bids.document', $bid),
                ],
            ],
            'statuses' => AppealStatus::options(),
            'staff' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                ->all(),
        ]);
    }

    public function update(UpdateTenderBidRequest $request, TenderBid $bid): RedirectResponse
    {
        $bid->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bid updated.')]);

        return to_route('admin.tender-bids.show', $bid);
    }

    public function destroy(TenderBid $bid): RedirectResponse
    {
        $bid->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bid deleted.')]);

        return to_route('admin.tender-bids.index');
    }

    /**
     * Controlled download of the bid document — commercial data on the private disk, served only to
     * staff with permission (ТЗ §12.5).
     */
    public function downloadDocument(TenderBid $bid): BinaryFileResponse
    {
        $document = $bid->getFirstMedia(TenderBid::DOCUMENT_COLLECTION);

        abort_if($document === null, 404);

        return response()->download($document->getPath(), $document->file_name);
    }
}
