<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    /**
     * Public frequently-asked-questions page (ТЗ §20 «й» — вопросы и ответы).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $faqs = Faq::published()
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Faq $faq) => [
                'id' => $faq->id,
                'question' => $faq->translation($locale)?->question,
                'answer' => $faq->translation($locale)?->answer,
            ])
            ->filter(fn (array $faq) => filled($faq['question']))
            ->values()
            ->all();

        return Inertia::render('public/faq/index', [
            'faqs' => $faqs,
        ]);
    }
}
