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
            'document_verifications.*.document_id' => 'required|integer',
            'document_verifications.*.status' => 'nullable|string|in:verified,missing,damaged',
            'document_verifications.*.notes' => 'nullable|string|max:500',
            'verification_notes' => 'nullable|string|max:1000',
            'force_complete_with_discrepancies' => 'boolean'
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
            'document_verifications.*.document_id.integer' => 'Document ID must be a valid integer.',
            'document_verifications.*.status.required' => 'Verification status is required for each document.',
            'document_verifications.*.status.in' => 'Verification status must be verified, missing, or damaged.',
            'document_verifications.*.notes.max' => 'Document notes cannot exceed 500 characters.',
            'verification_notes.max' => 'Verification notes cannot exceed 1000 characters.'
        ];
    }
}
