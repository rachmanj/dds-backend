<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributionRequest extends FormRequest
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
        $rules = [];

        if ($this->isMethod('POST')) {
            // Rules for creating a distribution
            $rules = [
                'document_type' => 'required|string|in:invoice,additional_document',
                'type_id' => 'required|integer|exists:distribution_types,id',
                'origin_department_id' => 'required|integer|exists:departments,id',
                'destination_department_id' => 'required|integer|exists:departments,id|different:origin_department_id',
                'notes' => 'nullable|string|max:1000',
                'documents' => 'required|array|min:1',
                'documents.*.type' => 'required|string|in:invoice,additional_document',
                'documents.*.id' => 'required|integer'
            ];
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Rules for updating a distribution
            $rules = [
                'type_id' => 'sometimes|required|integer|exists:distribution_types,id',
                'destination_department_id' => 'sometimes|required|integer|exists:departments,id',
                'notes' => 'nullable|string|max:1000'
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document_type.required' => 'Document type is required.',
            'document_type.in' => 'Document type must be either invoice or additional_document.',
            'type_id.required' => 'Distribution type is required.',
            'type_id.exists' => 'Selected distribution type does not exist.',
            'origin_department_id.required' => 'Origin department is required.',
            'origin_department_id.exists' => 'Selected origin department does not exist.',
            'destination_department_id.required' => 'Destination department is required.',
            'destination_department_id.exists' => 'Selected destination department does not exist.',
            'destination_department_id.different' => 'Destination department must be different from origin department.',
            'documents.required' => 'At least one document is required.',
            'documents.min' => 'At least one document is required.',
            'documents.*.type.required' => 'Document type is required for each document.',
            'documents.*.type.in' => 'Document type must be either invoice or additional_document.',
            'documents.*.id.required' => 'Document ID is required for each document.',
            'documents.*.id.integer' => 'Document ID must be a valid integer.',
            'notes.max' => 'Notes cannot exceed 1000 characters.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->isMethod('POST')) {
                // Validate document type consistency
                $documentType = $this->input('document_type');
                $documents = $this->input('documents', []);

                foreach ($documents as $index => $document) {
                    if (isset($document['type']) && $document['type'] !== $documentType) {
                        $validator->errors()->add(
                            "documents.{$index}.type",
                            "All documents must match the selected document type: {$documentType}"
                        );
                    }
                }
            }
        });
    }
}
