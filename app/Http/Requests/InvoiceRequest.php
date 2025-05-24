<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'receive_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'po_no' => 'nullable|string|max:30',
            'receive_project' => 'nullable|string|max:30',
            'invoice_project' => 'nullable|string|max:30',
            'payment_project' => 'nullable|string|max:30',
            'currency' => 'required|string|size:3',
            'amount' => 'required|numeric',
            'type_id' => 'required|exists:invoice_types,id',
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'cur_loc' => 'nullable|string|max:30',
            'status' => 'required|string|max:20',
            'created_by' => 'required|exists:users,id',
            'duration1' => 'nullable|integer',
            'duration2' => 'nullable|integer',
            'sap_doc' => 'nullable|string|max:20',
            'flag' => 'nullable|string|max:30',
        ];
    }
} 