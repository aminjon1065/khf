<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Enums\ContentStatus;
use App\Models\Language;
use App\Services\Cms\EditorialWorkflow;
use App\Support\PublicationScheduler;

/**
 * Shared helpers for CMS form data (locales, publication workflow).
 */
trait BuildsCmsFormData
{
    /**
     * @return list<array{code: string, native_name: string}>
     */
    protected function localeOptions(): array
    {
        return Language::active()
            ->map(fn (Language $language) => [
                'code' => $language->code,
                'native_name' => $language->native_name,
            ])
            ->all();
    }

    /**
     * @return array{statuses: list<array{value: string, label: string}>, statusTransitions: list<array{value: string, label: string}>, defaultLocale: string}
     */
    protected function publicationFormMeta(?ContentStatus $currentStatus = null): array
    {
        $status = $currentStatus ?? ContentStatus::Draft;
        $workflow = app(EditorialWorkflow::class);

        return [
            'statuses' => PublicationScheduler::statusOptions(),
            'statusTransitions' => $workflow->transitionOptions($status, auth()->user()),
            'canPublish' => $workflow->canPublish(auth()->user()),
            'defaultLocale' => Language::defaultCode(),
        ];
    }
}
