<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachDocumentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documents' => 'required|array|min:1',
            'documents.*.type' => 'required|string|in:invoice,additional_document',
            'documents.*.id' => 'required|integer'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'documents.required' => 'Documents are required.',
            'documents.array' => 'Documents must be an array.',
            'documents.min' => 'At least one document is required.',
            'documents.*.type.required' => 'Document type is required for each document.',
            'documents.*.type.in' => 'Document type must be either invoice or additional_document.',
            'documents.*.id.required' => 'Document ID is required for each document.',
            'documents.*.id.integer' => 'Document ID must be a valid integer.'
        ];
    }
}
