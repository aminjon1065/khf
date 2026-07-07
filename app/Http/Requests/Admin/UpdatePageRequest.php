<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdatePageRequest extends StorePageRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['status']);

        $page = $this->route('page');
        $current = $page instanceof Page ? $page->status : null;

        return array_merge($rules, $this->statusTransitionRules($current));
    }

    /**
     * Exclude the page being edited from the per-locale slug uniqueness check.
     */
    protected function currentPageId(): ?int
    {
        $page = $this->route('page');

        return $page instanceof Page ? $page->id : null;
    }
}
