<?php

namespace App\Http\Requests;

use App\Rules\SafeFileUpload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenderBidRequest extends FormRequest
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
     * The uploaded bid document is restricted by MIME type and size (ТЗ §12.4 secure uploads,
     * §35 — accepted formats DOC/DOCX/PDF/XLS/XLSX/ZIP).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'proposal' => ['nullable', 'string', 'max:5000'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip', 'max:10240', new SafeFileUpload],
            // Honeypot — must stay empty; bots that fill it are rejected (ТЗ §12.4 anti-spam).
            'website' => ['prohibited'],
        ];
    }
}
