<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        return [
            'title'                    => ['required', 'string', 'max:255'],
            'abstract'                 => ['nullable', 'string', 'max:1500'],
            'adviser_id'               => ['required', 'integer', 'exists:users,id'],
            'manuscript'               => ['required', 'file', 'mimes:pdf,docx', 'max:20480'], // 20 MB
            'keywords'                 => ['nullable', 'array', 'max:10'],
            'keywords.*'               => ['string', 'max:100'],
            'authors'                  => ['required', 'array', 'min:1'],
            'authors.*.first_name'     => ['required', 'string', 'max:191'],
            'authors.*.last_name'      => ['required', 'string', 'max:191'],
            'authors.*.is_submitter'   => ['required', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'authors.required' => 'At least one author is required before you can submit the paper.',
            'authors.min' => 'At least one author is required before you can submit the paper.',
            'authors.*.first_name.required' => 'Each author must have a first name.',
            'authors.*.last_name.required' => 'Each author must have a last name.',
            'adviser_id.required' => 'Select a research adviser before submitting the paper.',
            'manuscript.required' => 'Upload your manuscript before submitting the paper.',
        ];
    }

    public function attributes(): array
    {
        return [
            'authors' => 'authors list',
            'adviser_id' => 'research adviser',
        ];
    }
}
