<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\PollType;
use App\Http\Requests\Concerns\ValidatesContentStatusTransition;
use App\Http\Requests\Concerns\ValidatesFromBlueprint;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePollRequest extends FormRequest
{
    use ValidatesContentStatusTransition;
    use ValidatesFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManagePolls->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules(null),
            $this->pollOptionRules(),
        );

        unset($rules['options.*.label'], $rules['options.*.sort_order']);

        $rules['ends_at'] = ['nullable', 'date', 'after_or_equal:starts_at'];

        return $rules;
    }

    protected function blueprintReference(): string
    {
        return 'poll.default';
    }

    /**
     * @return array<string, list<string>>
     */
    protected function blueprintSelectOptions(): array
    {
        return [
            'type' => PollType::values(),
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function pollOptionRules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();

        $rules = [
            'options' => ['required', 'array', 'min:2', 'max:20'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.sort_order' => ['integer', 'min:0', 'max:65535'],
        ];

        foreach ($locales as $locale) {
            $rules["options.*.translations.{$locale}.label"] = [
                $locale === $default ? 'required' : 'nullable',
                'string',
                'max:255',
            ];
        }

        return $rules;
    }
}
