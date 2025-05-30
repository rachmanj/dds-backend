<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceAttachmentRequest extends FormRequest
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
            'description' => 'nullable|string|max:255',
        ];

        // Different rules for upload vs update
        if ($this->isMethod('post')) {
            // Get configuration values
            $maxFileSize = config('attachments.max_file_size') / 1024; // Convert to KB for validation
            $allowedExtensions = implode(',', config('attachments.allowed_extensions'));
            $allowedMimes = config('attachments.allowed_mime_types');

            // For file upload
            $rules['file'] = [
                'required',
                'file',
                "max:{$maxFileSize}",
                "mimes:{$allowedExtensions}",
                'mimetypes:' . implode(',', $allowedMimes)
            ];
        } else {
            // For update (only description can be updated)
            $rules['description'] = 'required|string|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxSizeMB = config('attachments.max_file_size') / 1048576; // Convert to MB

        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => "The file size must not exceed {$maxSizeMB} MB.",
            'file.mimes' => config('attachments.validation_messages.invalid_file_type'),
            'file.mimetypes' => config('attachments.validation_messages.invalid_file_type'),
            'description.required' => 'Please provide a description for the attachment.',
            'description.string' => 'The description must be a valid text.',
            'description.max' => 'The description must not exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'attachment file',
            'description' => 'file description',
        ];
    }
}
