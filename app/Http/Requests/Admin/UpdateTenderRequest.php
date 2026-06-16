<?php

namespace App\Http\Requests\Admin;

use App\Models\Tender;

class UpdateTenderRequest extends StoreTenderRequest
{
    /**
     * Exclude the tender being edited from the per-locale slug uniqueness check.
     */
    protected function currentTenderId(): ?int
    {
        $tender = $this->route('tender');

        return $tender instanceof Tender ? $tender->id : null;
    }
}
