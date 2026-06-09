<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    /**
     * Exclude the category being edited from the per-locale slug uniqueness check.
     */
    protected function currentCategoryId(): ?int
    {
        $category = $this->route('category');

        if ($category instanceof Category) {
            return $category->id;
        }

        return is_numeric($category) ? (int) $category : null;
    }
}
