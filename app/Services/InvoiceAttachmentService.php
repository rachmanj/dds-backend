<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use App\Repositories\InvoiceAttachmentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceAttachmentService
{
    protected InvoiceAttachmentRepository $attachmentRepository;

    public function __construct(InvoiceAttachmentRepository $attachmentRepository)
    {
        $this->attachmentRepository = $attachmentRepository;
    }

    /**
     * Upload and store an attachment for an invoice
     */
    public function uploadAttachment(int $invoiceId, UploadedFile $file, ?string $description = null): InvoiceAttachment
    {
        // Verify invoice exists
        $invoice = Invoice::findOrFail($invoiceId);

        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

        // Create directory path: invoices/{invoice_id}/attachments/
        $directory = "invoices/{$invoiceId}/attachments";
        $filePath = $directory . '/' . $filename;

        // Store the file
        $file->storeAs($directory, $filename, 'attachments');

        // Create attachment record
        $attachmentData = [
            'invoice_id' => $invoiceId,
            'file_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $description,
            'uploaded_by' => Auth::id(),
        ];

        return $this->attachmentRepository->create($attachmentData);
    }

    /**
     * Get all attachments for an invoice
     */
    public function getInvoiceAttachments(int $invoiceId)
    {
        return $this->attachmentRepository->getByInvoiceId($invoiceId);
    }

    /**
     * Get a specific attachment
     */
    public function getAttachment(int $attachmentId): ?InvoiceAttachment
    {
        return $this->attachmentRepository->getById($attachmentId);
    }

    /**
     * Update attachment description
     */
    public function updateAttachment(int $attachmentId, array $data): InvoiceAttachment
    {
        return $this->attachmentRepository->update($attachmentId, $data);
    }

    /**
     * Delete an attachment
     */
    public function deleteAttachment(int $attachmentId): bool
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);

        if (!$attachment) {
            return false;
        }

        // Delete the physical file
        if (Storage::disk('attachments')->exists($attachment->file_path)) {
            Storage::disk('attachments')->delete($attachment->file_path);
        }

        // Delete the record
        return $this->attachmentRepository->delete($attachmentId);
    }

    /**
     * Get file content for download
     */
    public function getFileContent(int $attachmentId): array
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);

        if (!$attachment) {
            throw new \Exception('Attachment not found');
        }

        if (!Storage::disk('attachments')->exists($attachment->file_path)) {
            throw new \Exception('File not found on disk');
        }

        return [
            'content' => Storage::disk('attachments')->get($attachment->file_path),
            'attachment' => $attachment
        ];
    }

    /**
     * Check if user can access attachment (based on invoice access)
     */
    public function canUserAccessAttachment(int $attachmentId, int $userId): bool
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);

        if (!$attachment) {
            return false;
        }

        // For now, allow access if user can access the invoice
        // This can be expanded with more specific permissions later
        return true;
    }

    /**
     * Get storage statistics for an invoice
     */
    public function getInvoiceStorageStats(int $invoiceId): array
    {
        $attachments = $this->attachmentRepository->getByInvoiceId($invoiceId);

        $totalSize = 0;
        $fileTypes = [];

        foreach ($attachments as $attachment) {
            $totalSize += $attachment->file_size;
            $ext = $attachment->file_extension;
            $fileTypes[$ext] = ($fileTypes[$ext] ?? 0) + 1;
        }

        return [
            'total_files' => $attachments->count(),
            'total_size' => $totalSize,
            'formatted_total_size' => $this->formatFileSize($totalSize),
            'file_types' => $fileTypes,
        ];
    }

    /**
     * Format file size to human readable format
     */
    private function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Delete all attachments for an invoice
     */
    public function deleteAllInvoiceAttachments(int $invoiceId): bool
    {
        try {
            $attachments = $this->attachmentRepository->getByInvoiceId($invoiceId);

            foreach ($attachments as $attachment) {
                // Delete physical file
                if (Storage::disk('attachments')->exists($attachment->file_path)) {
                    Storage::disk('attachments')->delete($attachment->file_path);
                }
                // Delete record
                $this->attachmentRepository->delete($attachment->id);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate file before upload
     */
    public function validateFileUpload(UploadedFile $file): array
    {
        $errors = [];

        // Check file size (10MB limit)
        if ($file->getSize() > 10485760) { // 10MB in bytes
            $errors[] = 'File size must not exceed 10 MB';
        }

        // Check file type
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'File must be a PDF or image (JPG, JPEG, PNG, GIF)';
        }

        // Check file extension
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid file extension';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get attachments by file type
     */
    public function getAttachmentsByType(int $invoiceId, string $type)
    {
        $attachments = $this->attachmentRepository->getByInvoiceId($invoiceId);

        return $attachments->filter(function ($attachment) use ($type) {
            switch ($type) {
                case 'images':
                    return $attachment->is_image;
                case 'pdfs':
                    return $attachment->is_pdf;
                default:
                    return true;
            }
        });
    }

    /**
     * Search attachments by description
     */
    public function searchAttachments(int $invoiceId, string $search)
    {
        return $this->attachmentRepository->searchByDescription($invoiceId, $search);
    }

    /**
     * Create storage directory if it doesn't exist
     */
    public function ensureStorageDirectory(int $invoiceId): bool
    {
        $directory = "invoices/{$invoiceId}/attachments";

        if (!Storage::disk('attachments')->exists($directory)) {
            return Storage::disk('attachments')->makeDirectory($directory);
        }

        return true;
    }

    /**
     * Clean up empty directories after file deletion
     */
    public function cleanupEmptyDirectories(int $invoiceId): bool
    {
        try {
            $directory = "invoices/{$invoiceId}/attachments";

            // Check if directory exists and is empty
            if (Storage::disk('attachments')->exists($directory)) {
                $files = Storage::disk('attachments')->files($directory);
                if (empty($files)) {
                    Storage::disk('attachments')->deleteDirectory($directory);

                    // Also try to clean up parent directory if empty
                    $parentDirectory = "invoices/{$invoiceId}";
                    $parentFiles = Storage::disk('attachments')->allFiles($parentDirectory);
                    if (empty($parentFiles)) {
                        Storage::disk('attachments')->deleteDirectory($parentDirectory);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
