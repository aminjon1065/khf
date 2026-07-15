<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Defence-in-depth guard for every uploaded file (ТЗ §12.4). Complements — never replaces — the
 * per-request `mimes:`/`max:` allowlist. It rejects anything that could execute in a browser on the
 * portal's own origin (stored XSS) or on the server, judging by the file's *real* content type
 * (finfo) rather than the spoofable client-supplied MIME, and by every interior filename segment so
 * a double extension such as `shell.php.jpg` cannot slip a PHP payload past a jpg allowlist.
 */
class SafeFileUpload implements ValidationRule
{
    /**
     * Extensions that must never be accepted, in any position of the filename.
     *
     * @var list<string>
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'phtml', 'phar', 'shtml',
        'exe', 'sh', 'bash', 'bat', 'cmd', 'com', 'cgi', 'pl', 'py', 'rb', 'jsp', 'asp', 'aspx',
        'js', 'mjs', 'html', 'htm', 'xhtml', 'xml', 'svg', 'svgz', 'xsl', 'htaccess',
    ];

    /**
     * Real (content-sniffed) MIME types that can execute in a browser or shell.
     *
     * @var list<string>
     */
    private const DANGEROUS_MIMES = [
        'application/x-executable', 'application/x-sh', 'application/x-httpd-php', 'text/x-php',
        'application/javascript', 'text/javascript', 'application/x-javascript',
        'text/html', 'application/xhtml+xml', 'image/svg+xml', 'text/xml', 'application/xml',
    ];

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a file.');

            return;
        }

        // Every dot-delimited segment of the original name — catches `shell.php.jpg`.
        $segments = array_map('strtolower', explode('.', $value->getClientOriginalName()));

        foreach ($segments as $segment) {
            if (in_array($segment, self::DANGEROUS_EXTENSIONS, true)) {
                $fail('The :attribute contains a forbidden file extension.');

                return;
            }
        }

        // Real content type (finfo), not the client-declared one which a caller can forge.
        $mime = strtolower($value->getMimeType() ?? '');

        if (in_array($mime, self::DANGEROUS_MIMES, true)) {
            $fail('The :attribute contains a forbidden file type.');
        }
    }
}
