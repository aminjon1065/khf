<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTouristGroupRequest extends FormRequest
{
    /**
     * Public form — anyone may submit (rate-limited at the route, anti-spam honeypot below).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'leader_name' => ['required', 'string', 'max:255'],
            'leader_phone' => ['required', 'string', 'max:50'],
            'leader_email' => ['nullable', 'string', 'email', 'max:255'],
            'participants_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'route' => ['required', 'string', 'max:5000'],
            'equipment' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'start_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'start_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // Honeypot anti-spam (ТЗ §12.4).
            'website' => ['prohibited'],
        ];
    }
}
