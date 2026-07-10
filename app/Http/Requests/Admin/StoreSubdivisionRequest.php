<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesContentStatusTransition;
use App\Http\Requests\Concerns\ValidatesFromBlueprint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubdivisionRequest extends FormRequest
{
    use ValidatesContentStatusTransition;
    use ValidatesFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageStructure->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules(null),
        );

        if (($id = $this->currentSubdivisionId()) !== null) {
            $rules['parent_id'][] = Rule::notIn([$id]);
        }

        return $rules;
    }

    protected function blueprintReference(): string
    {
        return 'subdivision.default';
    }

    protected function currentSubdivisionId(): ?int
    {
        return null;
    }
}
