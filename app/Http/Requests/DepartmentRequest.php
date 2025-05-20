<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'project' => 'required|string|max:10',
            'location_code' => 'required|string|max:30|unique:departments,location_code',
            'transit_code' => 'nullable|string|max:30|unique:departments,transit_code',
            'akronim' => 'required|string|max:20|unique:departments,akronim',
            'sap_code' => 'nullable|string|max:20|unique:departments,sap_code',
        ];
    }
}
