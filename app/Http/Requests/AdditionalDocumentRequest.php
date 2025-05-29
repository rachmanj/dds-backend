<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdditionalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $documentId = $this->route('additional_document') ?? $this->input('document_id');

        return [
            'type_id' => 'required|exists:additional_document_types,id',
            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('additional_documents', 'document_number')
                    ->where(function ($query) {
                        return $query->where('type_id', $this->input('type_id'));
                    })
                    ->ignore($documentId)
            ],
            'document_date' => 'required|date',
            'po_no' => 'nullable|string|max:50',
            'receive_date' => 'nullable|date',
            'created_by' => 'nullable|exists:users,id',
            'remarks' => 'nullable|string',
            'cur_loc' => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.unique' => 'This document number already exists for the selected document type.',
            'type_id.required' => 'Please select a document type.',
            'type_id.exists' => 'The selected document type is invalid.',
            'document_number.required' => 'Please enter the document number.',
            'document_date.required' => 'Please select a document date.',
        ];
    }
}
