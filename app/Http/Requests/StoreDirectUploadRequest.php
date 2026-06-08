<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDirectUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'title'                => ['required', 'string', 'max:255'],
            'abstract'             => ['nullable', 'string', 'max:1500'],
            'course_id'            => ['required', 'integer', 'exists:courses,id'],
            'adviser_id'           => ['nullable', 'integer', 'exists:users,id'],
            'published_year'       => ['required', 'integer'],
            'manuscript'           => ['required', 'file', 'mimes:pdf,docx', 'max:20480'],
            'keywords'             => ['nullable', 'array', 'max:10'],
            'keywords.*'           => ['string', 'max:100'],
            'authors'              => ['required', 'array', 'min:1'],
            'authors.*.first_name' => ['required', 'string', 'max:191'],
            'authors.*.last_name'  => ['required', 'string', 'max:191'],
        ];
    }

    public function messages(): array
    {
        return [
            'authors.required' => 'Add at least one author before uploading the paper.',
            'authors.min' => 'Add at least one author before uploading the paper.',
            'authors.*.first_name.required' => 'Each author must have a first name.',
            'authors.*.last_name.required' => 'Each author must have a last name.',
            'manuscript.required' => 'Upload the manuscript before saving the paper.',
        ];
    }

    public function attributes(): array
    {
        return [
            'course_id' => 'program',
            'published_year' => 'year published',
            'authors' => 'authors list',
        ];
    }
}
