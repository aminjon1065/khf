<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppealStatus;
use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTouristGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageTouristGroups->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(AppealStatus::values())],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
