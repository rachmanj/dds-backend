<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDistributionRequest extends FormRequest
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
            'document_verifications' => 'nullable|array',
            'document_verifications.*.document_type' => 'required|string|in:invoice,additional_document',
            'document_verifications.*.document_id' => 'required|integer'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document_verifications.array' => 'Document verifications must be an array.',
            'document_verifications.*.document_type.required' => 'Document type is required for each verification.',
            'document_verifications.*.document_type.in' => 'Document type must be either invoice or additional_document.',
            'document_verifications.*.document_id.required' => 'Document ID is required for each verification.',
            'document_verifications.*.document_id.integer' => 'Document ID must be a valid integer.'
        ];
    }
}
