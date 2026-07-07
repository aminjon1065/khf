<?php

namespace App\Http\Requests\Admin;

use App\Models\Vacancy;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateVacancyRequest extends StoreVacancyRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['status']);

        $vacancy = $this->route('vacancy');
        $current = $vacancy instanceof Vacancy ? $vacancy->status : null;

        return array_merge($rules, $this->statusTransitionRules($current));
    }

    /**
     * Exclude the vacancy being edited from the per-locale slug uniqueness check.
     */
    protected function currentVacancyId(): ?int
    {
        $vacancy = $this->route('vacancy');

        return $vacancy instanceof Vacancy ? $vacancy->id : null;
    }
}
