<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'email'           => ['required', 'string', 'max:320'],
            'smtp_validation' => ['sometimes', 'boolean'],
            'skip_cache'      => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'An email address is required.',
            'email.max'      => 'Email address must not exceed 320 characters.',
        ];
    }
}
