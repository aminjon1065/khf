<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesContentStatusTransition;
use App\Http\Requests\Concerns\ValidatesFromBlueprint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStatisticRequest extends FormRequest
{
    use ValidatesContentStatusTransition;
    use ValidatesFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageStatistics->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules(null),
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('year') === '' || $this->input('year') === null) {
            $this->merge(['year' => null]);
        }
    }

    protected function blueprintReference(): string
    {
        return 'statistic.default';
    }
}
