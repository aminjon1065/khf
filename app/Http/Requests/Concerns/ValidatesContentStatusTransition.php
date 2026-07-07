<?php

namespace App\Http\Requests\Concerns;

use App\Enums\ContentStatus;
use App\Enums\Permission;
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

        if ($currentStatus !== null) {
            $rules['status'][] = function (string $attribute, mixed $value, Closure $fail) use ($currentStatus): void {
                $target = ContentStatus::from((string) $value);

                if ($target === $currentStatus) {
                    return;
                }

                if (! $currentStatus->canTransitionTo($target)) {
                    $fail(__('Invalid status transition.'));
                }
            };
        }

        return $rules;
    }

    protected function userCanPublish(): bool
    {
        return $this->user()?->can(Permission::PublishPosts->value) ?? false;
    }
}
