<?php

namespace App\Http\Requests\Admin;

use App\Enums\EmploymentType;
use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesContentStatusTransition;
use App\Http\Requests\Concerns\ValidatesFromBlueprint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacancyRequest extends FormRequest
{
    use ValidatesContentStatusTransition;
    use ValidatesFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageVacancies->value) ?? false;
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

    protected function blueprintReference(): string
    {
        return 'vacancy.default';
    }

    /**
     * @return array<string, array{table: string, column: string, foreign_key: string, exclude_id: int|null}>
     */
    protected function blueprintSlugConstraints(): array
    {
        return [
            'slug' => [
                'table' => 'vacancy_translations',
                'column' => 'slug',
                'foreign_key' => 'vacancy_id',
                'exclude_id' => $this->currentVacancyId(),
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function blueprintSelectOptions(): array
    {
        return [
            'employment_type' => EmploymentType::values(),
        ];
    }

    protected function currentVacancyId(): ?int
    {
        return null;
    }
}
