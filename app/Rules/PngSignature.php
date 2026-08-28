<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A signature drawn on the canvas pad, arriving as a base64 PNG data URI.
 *
 * Only a real PNG is accepted: whatever passes here is embedded straight
 * into a PDF by dompdf, so the payload is decoded and checked against the
 * PNG magic number rather than trusted for its prefix.
 *
 * Shared by every document a signature lands on — the parent's invoice, the
 * aide's and parent's time sheet — so the same payload is rejected the same
 * way wherever it is drawn.
 */
class PngSignature implements ValidationRule
{
    private const PREFIX = 'data:image/png;base64,';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_starts_with($value, self::PREFIX)) {
            $fail('The :attribute must be a PNG image.');

            return;
        }

        $decoded = base64_decode(substr($value, strlen(self::PREFIX)), true);

        if ($decoded === false || $decoded === '') {
            $fail('The :attribute could not be read. Please draw it again.');

            return;
        }

        if (! str_starts_with($decoded, "\x89PNG\r\n\x1a\n")) {
            $fail('The :attribute must be a PNG image.');
        }
    }
}
