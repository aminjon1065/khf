<?php

namespace App\Http\Controllers\Public;

use App\Enums\AlertStatus;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AlertController extends Controller
{
    /**
     * Public detail page for an emergency alert (ТЗ §6.4.1). The banner, e-mail and web-push
     * notifications all deep-link here. Published alerts remain reachable after their window closes
     * (a notification may arrive late) but are flagged expired; drafts and cancelled alerts 404.
     */
    public function show(string $locale, Alert $alert): Response
    {
        if ($alert->status !== AlertStatus::Published) {
            throw new NotFoundHttpException;
        }

        $translation = $alert->translation($locale);
        $now = Carbon::now();
        $isActive = ($alert->starts_at === null || $alert->starts_at->lte($now))
            && ($alert->ends_at === null || $alert->ends_at->gte($now));

        $region = $alert->region_id !== null
            ? $alert->region()->with('translations')->first()?->translation($locale)?->name
            : null;

        return Inertia::render('public/alerts/show', [
            'alert' => [
                'id' => $alert->id,
                'level' => $alert->hazard_level->value,
                'level_label' => $alert->hazard_level->label(),
                'color' => $alert->hazard_level->color(),
                'title' => $translation?->title,
                'body' => $translation?->body,
                'region' => $region,
                'published_at' => ($alert->starts_at ?? $alert->created_at)?->toIso8601String(),
                'expires_at' => $alert->ends_at?->toIso8601String(),
                'is_active' => $isActive,
            ],
            'seo' => [
                'title' => $translation?->title,
                'description' => $translation?->body,
            ],
        ]);
    }
}
