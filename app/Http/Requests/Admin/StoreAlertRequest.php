<?php

namespace App\Http\Requests\Admin;

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesFromBlueprint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlertRequest extends FormRequest
{
    use ValidatesFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageAlerts->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->blueprintRules();
        $rules['ends_at'] = ['nullable', 'date', 'after_or_equal:starts_at'];

        return $rules;
    }

    protected function blueprintReference(): string
    {
        return 'alert.default';
    }

    /**
     * @return array<string, list<string>>
     */
    protected function blueprintSelectOptions(): array
    {
        return [
            'hazard_level' => HazardLevel::values(),
            'status' => AlertStatus::values(),
        ];
    }
}
