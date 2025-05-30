<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Attachments Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for invoice attachments
    | including file size limits, allowed types, and storage settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    */
    'max_file_size' => env('ATTACHMENT_MAX_FILE_SIZE', 10485760), // 10MB in bytes
    'max_files_per_invoice' => env('ATTACHMENT_MAX_FILES_PER_INVOICE', 50),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    */
    'allowed_mime_types' => [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
    ],

    'allowed_extensions' => [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'gif',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage_disk' => env('ATTACHMENT_STORAGE_DISK', 'attachments'),
    'storage_path_template' => 'invoices/{invoice_id}/attachments',

    /*
    |--------------------------------------------------------------------------
    | File Organization
    |--------------------------------------------------------------------------
    */
    'filename_template' => '{timestamp}_{slug}.{extension}',
    'preserve_original_names' => true,

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'scan_for_viruses' => env('ATTACHMENT_VIRUS_SCAN', false),
    'validate_file_headers' => true,
    'check_file_content' => true,

    /*
    |--------------------------------------------------------------------------
    | Display Settings
    |--------------------------------------------------------------------------
    */
    'inline_preview_types' => [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
    ],

    'thumbnail_support' => [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    */
    'auto_cleanup_empty_directories' => true,
    'orphaned_file_cleanup_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */
    'validation_messages' => [
        'file_too_large' => 'File size must not exceed :max_size MB',
        'invalid_file_type' => 'File must be a PDF or image (PDF, JPG, JPEG, PNG, GIF)',
        'too_many_files' => 'Cannot upload more than :max_files files per invoice',
        'file_corrupted' => 'The uploaded file appears to be corrupted',
        'virus_detected' => 'The uploaded file failed security scan',
    ],

];
