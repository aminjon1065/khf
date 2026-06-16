<?php

namespace App\Http\Requests\Admin;

use App\Models\Vacancy;

class UpdateVacancyRequest extends StoreVacancyRequest
{
    /**
     * Exclude the vacancy being edited from the per-locale slug uniqueness check.
     */
    protected function currentVacancyId(): ?int
    {
        $vacancy = $this->route('vacancy');

        return $vacancy instanceof Vacancy ? $vacancy->id : null;
    }
}
