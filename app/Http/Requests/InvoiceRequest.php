<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice') ?? $this->input('invoice_id'); // Get invoice ID from route or request data
        $supplierId = $this->input('supplier_id');

        return [
            'invoice_id' => 'sometimes|integer', // Allow invoice_id for updates
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('invoices', 'invoice_number')
                    ->where(function ($query) use ($supplierId) {
                        if ($supplierId) {
                            return $query->where('supplier_id', $supplierId);
                        }
                        return $query;
                    })
                    ->ignore($invoiceId)
            ],
            'faktur_no' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'receive_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'po_no' => 'nullable|string|max:30',
            'receive_project' => 'nullable|string|max:30',
            'invoice_project' => 'nullable|string|max:30',
            'payment_project' => 'nullable|string|max:30',
            'currency' => 'required|string|size:3',
            'amount' => 'required|numeric|min:0',
            'type_id' => 'required|exists:invoice_types,id',
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'cur_loc' => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.unique' => 'This invoice number already exists for the selected supplier.',
            'supplier_id.required' => 'Please select a supplier.',
            'supplier_id.exists' => 'The selected supplier is invalid.',
            'type_id.required' => 'Please select an invoice type.',
            'type_id.exists' => 'The selected invoice type is invalid.',
            'amount.required' => 'Please enter the invoice amount.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be greater than or equal to 0.',
            'currency.required' => 'Please select a currency.',
            'currency.size' => 'Currency must be exactly 3 characters.',
            'invoice_date.required' => 'Please select an invoice date.',
            'receive_date.required' => 'Please select a receive date.',
        ];
    }
}
