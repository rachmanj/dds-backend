<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportAdditionalDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('documents.import');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'check_duplicates' => 'boolean'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to import.',
            'file.file' => 'The uploaded file is not valid.',
            'file.mimes' => 'The file must be an Excel file (.xlsx, .xls) or CSV file (.csv).',
            'file.max' => 'The file size must not exceed 10MB.',
        ];
    }
}
