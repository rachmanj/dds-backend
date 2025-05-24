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
            'project' => 'nullable|string|max:50',
            'receive_date' => 'nullable|date',
            'created_by' => 'required|exists:users,id',
            'attachment' => 'nullable|string',
            'remarks' => 'nullable|string',
            'flag' => 'nullable|string|max:30',
            'status' => 'required|string|max:20',
            'cur_loc' => 'nullable|string|max:30',
            'ito_creator' => 'nullable|string|max:50',
            'grpo_no' => 'nullable|string|max:20',
            'origin_wh' => 'nullable|string|max:20',
            'destination_wh' => 'nullable|string|max:20',
            'batch_no' => 'nullable|integer',
        ];
    }
} 