<?php

namespace App\Http\Requests\Concerns;

use App\Enums\ContentStatus;
use App\Services\Cms\EditorialWorkflow;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

trait ValidatesContentStatusTransition
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function statusTransitionRules(?ContentStatus $currentStatus): array
    {
        $rules = [
            'status' => ['required', 'in:'.implode(',', ContentStatus::values())],
        ];

        $rules['status'][] = function (string $attribute, mixed $value, Closure $fail) use ($currentStatus): void {
            $target = ContentStatus::from((string) $value);
            $from = $currentStatus ?? ContentStatus::Draft;

            if ($target === $from) {
                return;
            }

            if (! app(EditorialWorkflow::class)->canTransition($this->user(), $from, $target)) {
                $fail(__('Invalid status transition.'));
            }
        };

        return $rules;
    }

    protected function userCanPublish(): bool
    {
        return app(EditorialWorkflow::class)->canPublish($this->user());
    }
}
