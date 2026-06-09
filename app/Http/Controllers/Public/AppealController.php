<?php

namespace App\Http\Controllers\Public;

use App\Enums\AppealCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppealRequest;
use App\Models\Appeal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppealController extends Controller
{
    /**
     * Public appeal form (ТЗ §6.7). Shows the assigned reference after a successful submission.
     */
    public function create(): Response
    {
        return Inertia::render('public/appeals/create', [
            'categories' => AppealCategory::options(),
            'submittedReference' => session('appeal_reference'),
        ]);
    }

    public function store(StoreAppealRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['website']);

        $appeal = Appeal::create([
            ...$data,
            'reference' => Appeal::generateReference(),
        ]);

        return to_route('appeals.create', ['locale' => app()->getLocale()])
            ->with('appeal_reference', $appeal->reference);
    }

    /**
     * Public status tracking by reference number (ТЗ §6.7).
     */
    public function track(Request $request): Response
    {
        $reference = trim((string) $request->string('reference'));
        $result = null;

        if ($reference !== '') {
            $appeal = Appeal::where('reference', $reference)->first();

            $result = $appeal === null
                ? ['found' => false]
                : [
                    'found' => true,
                    'reference' => $appeal->reference,
                    'subject' => $appeal->subject,
                    'category' => $appeal->category->label(),
                    'status' => $appeal->status->label(),
                    'created_at' => $appeal->created_at?->format('d.m.Y'),
                    'updated_at' => $appeal->updated_at?->format('d.m.Y'),
                ];
        }

        return Inertia::render('public/appeals/track', [
            'reference' => $reference,
            'result' => $result,
        ]);
    }
}
