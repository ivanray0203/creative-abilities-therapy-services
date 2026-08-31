<?php

namespace App\Http\Requests;

use App\Rules\PngSignature;
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
            'signature' => ['required', 'string', 'max:2000000', new PngSignature],
        ];
    }
}
