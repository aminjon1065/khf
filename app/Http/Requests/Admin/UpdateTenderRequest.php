<?php

namespace App\Http\Requests\Admin;

use App\Models\Tender;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateTenderRequest extends StoreTenderRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['status']);

        $tender = $this->route('tender');
        $current = $tender instanceof Tender ? $tender->status : null;

        return array_merge($rules, $this->statusTransitionRules($current));
    }

    /**
     * Exclude the tender being edited from the per-locale slug uniqueness check.
     */
    protected function currentTenderId(): ?int
    {
        $tender = $this->route('tender');

        return $tender instanceof Tender ? $tender->id : null;
    }
}
