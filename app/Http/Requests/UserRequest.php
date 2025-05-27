<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $userId = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'nik' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'password' => $this->isMethod('POST') ? 'required|string|min:8' : 'nullable|string|min:8',
            'project' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email already exists',
            'username.required' => 'Username is required',
            'username.unique' => 'Username already exists',
            'nik.required' => 'NIK is required',
            'nik.unique' => 'NIK already exists',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'department_id.exists' => 'Selected department does not exist',
            'roles.array' => 'Roles must be an array',
            'roles.*.exists' => 'Selected role does not exist',
        ];
    }
}
