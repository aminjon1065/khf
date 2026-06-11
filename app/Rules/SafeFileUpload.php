<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SafeFileUpload implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a file.');
            return;
        }

        $dangerousExtensions = ['php', 'exe', 'sh', 'bat', 'cmd', 'phtml', 'phar', 'cgi', 'pl', 'jsp', 'asp', 'js'];
        $dangerousMimes = [
            'application/x-executable',
            'application/x-sh',
            'application/x-httpd-php',
            'text/x-php',
            'application/javascript',
        ];

        $extension = strtolower($value->getClientOriginalExtension());
        $mime = strtolower($value->getClientMimeType());

        if (in_array($extension, $dangerousExtensions, true)) {
            $fail('The :attribute contains a forbidden file extension.');
            return;
        }

        if (in_array($mime, $dangerousMimes, true)) {
            $fail('The :attribute contains a forbidden MIME type.');
            return;
        }
        
        if ($extension === 'svg' && $mime === 'image/svg+xml') {
            $content = file_get_contents($value->getRealPath());
            if (stripos($content, '<script') !== false || stripos($content, 'onmouseover') !== false || stripos($content, 'onclick') !== false) {
                $fail('The :attribute SVG file contains potentially malicious scripts.');
                return;
            }
        }
    }
}
