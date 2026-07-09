<?php

namespace App\Http\Requests\Concerns;

use App\Cms\Blueprint\BlueprintRepository;
use App\Cms\Blueprint\BlueprintValidator;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Merges blueprint-driven validation rules with workflow-specific rules.
 */
trait ValidatesFromBlueprint
{
    abstract protected function blueprintReference(): string;

    /**
     * @return array<string, array{table: string, column: string, foreign_key: string, exclude_id: int|null}>
     */
    protected function blueprintSlugConstraints(): array
    {
        return [];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function blueprintSelectOptions(): array
    {
        return [];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function blueprintRules(): array
    {
        $blueprint = app(BlueprintRepository::class)->find($this->blueprintReference());

        return app(BlueprintValidator::class)->rules(
            $blueprint,
            Language::codes() ?: config('app.locales'),
            Language::defaultCode(),
            $this->blueprintSlugConstraints(),
            $this->blueprintSelectOptions(),
        );
    }
}
