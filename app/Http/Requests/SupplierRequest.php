<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
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
        $rules = [
            'sap_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50|in:vendor,customer',
            'city' => 'nullable|string|max:255',
            'payment_project' => 'required|string|max:10',
            'is_active' => 'boolean',
            'address' => 'nullable|string',
            'npwp' => 'nullable|string|max:50',
        ];

        // Only require created_by for POST requests (create)
        if ($this->isMethod('POST')) {
            $rules['created_by'] = 'required|exists:users,id';
        }

        return $rules;
    }
}
