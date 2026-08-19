<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The parent returning a signed invoice. The signature arrives as a PNG
 * data URI drawn on a canvas, so it is validated by shape rather than as an
 * uploaded file.
 */
class SignInvoiceRequest extends FormRequest
{
    /** Authorisation is the controller's policy check, which needs the invoice. */
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
            'signature' => ['required', 'string', 'max:2000000', $this->pngDataUri()],
        ];
    }

    /**
     * Only a base64 PNG data URI is accepted — anything else would be
     * embedded straight into the PDF by dompdf.
     */
    private function pngDataUri(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! str_starts_with($value, 'data:image/png;base64,')) {
                $fail('The signature must be a PNG image.');

                return;
            }

            $payload = substr($value, strlen('data:image/png;base64,'));
            $decoded = base64_decode($payload, true);

            if ($decoded === false || $decoded === '') {
                $fail('The signature could not be read. Please draw it again.');

                return;
            }

            // PNG magic number, so a renamed payload cannot reach dompdf.
            if (! str_starts_with($decoded, "\x89PNG\r\n\x1a\n")) {
                $fail('The signature must be a PNG image.');
            }
        };
    }
}
