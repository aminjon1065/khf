<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacancyApplicationRequest extends FormRequest
{
    /**
     * Public form — anyone may submit (rate-limited at the route, anti-spam honeypot below).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The uploaded questionnaire/CV is restricted by MIME type and size (ТЗ §12.4 secure uploads,
     * §35 — accepted document formats DOC/DOCX/PDF).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            // Honeypot — must stay empty; bots that fill it are rejected (ТЗ §12.4 anti-spam).
            'website' => ['prohibited'],
        ];
    }
}
