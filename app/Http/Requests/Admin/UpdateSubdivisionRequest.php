<?php

namespace App\Http\Requests\Admin;

use App\Models\Subdivision;

class UpdateSubdivisionRequest extends StoreSubdivisionRequest
{
    /**
     * Exclude the subdivision being edited from being selected as its own parent.
     */
    protected function currentSubdivisionId(): ?int
    {
        $subdivision = $this->route('subdivision');

        return $subdivision instanceof Subdivision ? $subdivision->id : null;
    }
}
