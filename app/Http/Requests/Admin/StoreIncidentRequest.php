<?php

namespace App\Http\Requests\Admin;

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesFromBlueprint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentRequest extends FormRequest
{
    use ValidatesFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageIncidents->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->blueprintRules();
    }

    protected function blueprintReference(): string
    {
        return 'incident.default';
    }

    /**
     * @return array<string, list<string>>
     */
    protected function blueprintSelectOptions(): array
    {
        return [
            'type' => IncidentType::values(),
            'hazard_level' => HazardLevel::values(),
            'status' => IncidentStatus::values(),
        ];
    }
}
