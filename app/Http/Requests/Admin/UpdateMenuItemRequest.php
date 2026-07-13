<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Http\Requests\Admin\Concerns\ValidatesMenuItemPayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    use ValidatesMenuItemPayload;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageMenus->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->menuItemRules();
    }
}
