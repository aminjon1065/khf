<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppealStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $appeal = $this->route('appeal');

        return $appeal !== null && ($this->user()?->can('update', $appeal) ?? false);
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
            'deadline_at' => ['nullable', 'date'],
        ];
    }
}
