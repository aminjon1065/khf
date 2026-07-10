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
        $vacancy = $this->route('vacancy');
        $current = $vacancy instanceof Vacancy ? $vacancy->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }

    protected function currentVacancyId(): ?int
    {
        $vacancy = $this->route('vacancy');

        if ($vacancy instanceof Vacancy) {
            return $vacancy->id;
        }

        return is_numeric($vacancy) ? (int) $vacancy : null;
    }
}
