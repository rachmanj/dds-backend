<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdditionalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_id' => 'required|exists:additional_document_types,id',
            'document_number' => 'required|string',
            'document_date' => 'required|date',
            'po_no' => 'nullable|string|max:50',
            'receive_date' => 'nullable|date',
            'created_by' => 'nullable|exists:users,id',
            'remarks' => 'nullable|string',
            'cur_loc' => 'nullable|string|max:30',
        ];
    }
}
