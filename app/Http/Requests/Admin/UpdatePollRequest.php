<?php

namespace App\Http\Requests\Admin;

use App\Models\Poll;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdatePollRequest extends StorePollRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $poll = $this->route('poll');
        $current = $poll instanceof Poll ? $poll->status : null;

        $rules = array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
            $this->pollOptionRules(),
        );

        unset($rules['options.*.label'], $rules['options.*.sort_order']);

        $rules['ends_at'] = ['nullable', 'date', 'after_or_equal:starts_at'];

        return $rules;
    }
}
