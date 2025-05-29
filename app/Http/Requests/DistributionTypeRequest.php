<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistributionTypeRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'color' => 'required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'priority' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string|max:1000'
        ];

        if ($this->isMethod('POST')) {
            // For creating new distribution type
            $rules['code'] = 'required|string|size:1|unique:distribution_types,code';
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // For updating existing distribution type
            $distributionTypeId = $this->route('distribution_type');
            $rules['name'] = 'sometimes|required|string|max:255';
            $rules['code'] = [
                'sometimes',
                'required',
                'string',
                'size:1',
                Rule::unique('distribution_types', 'code')->ignore($distributionTypeId)
            ];
            $rules['color'] = 'sometimes|required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/';
            $rules['priority'] = 'sometimes|required|integer|min:1|max:10';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Distribution type name is required.',
            'name.max' => 'Distribution type name cannot exceed 255 characters.',
            'code.required' => 'Distribution type code is required.',
            'code.size' => 'Distribution type code must be exactly 1 character.',
            'code.unique' => 'This distribution type code already exists.',
            'color.required' => 'Color is required.',
            'color.size' => 'Color must be exactly 7 characters (including #).',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF0000).',
            'priority.required' => 'Priority is required.',
            'priority.integer' => 'Priority must be a number.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority cannot exceed 10.',
            'description.max' => 'Description cannot exceed 1000 characters.'
        ];
    }
}
