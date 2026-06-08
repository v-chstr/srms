<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'regex:/^[A-Za-z\s]+$/', 'max:25'],
            'last_name'  => ['required', 'string', 'regex:/^[A-Za-z\s]+$/', 'max:25'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'First name may only contain letters and spaces.',
            'last_name.regex'  => 'Last name may only contain letters and spaces.',
            'email.unique'     => 'That email address is already taken.',
        ];
    }
}
