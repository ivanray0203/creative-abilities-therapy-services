<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A parent returning their signed consent package. A scanned PDF is the
 * expected shape, but phone photos of the signed pages are accepted too.
 */
class UploadSignedConsentRequest extends FormRequest
{
    /** Authorisation is the signed URL on the route. */
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
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Please choose your signed consent to upload.',
            'files.*.mimes' => 'Each file must be a PDF, JPG or PNG.',
            'files.*.max' => 'Each file must be 10 MB or smaller.',
        ];
    }
}
