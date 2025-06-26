<?php

namespace App\Services;

use App\Models\FileWatermark;
use App\Models\InvoiceAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class WatermarkService
{
    /**
     * Add watermark to a file based on its type
     */
    public function addWatermark(string $filePath, ?string $watermarkText = null, array $options = []): string
    {
        $fullPath = storage_path('app/' . $filePath);

        if (!file_exists($fullPath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $mimeType = mime_content_type($fullPath);
        $watermarkText = $watermarkText ?? $this->generateDefaultWatermark();

        switch (true) {
            case str_starts_with($mimeType, 'image/'):
                return $this->addImageWatermark($filePath, $watermarkText, $options);

            case $mimeType === 'application/pdf':
                return $this->addPdfWatermark($filePath, $watermarkText, $options);

            default:
                // For unsupported file types, create a copy with metadata
                return $this->createWatermarkCopy($filePath, $watermarkText);
        }
    }

    /**
     * Add watermark to image files (simplified version)
     */
    public function addImageWatermark(string $filePath, string $watermarkText, array $options = []): string
    {
        try {
            $fullPath = storage_path('app/' . $filePath);

            // For now, create a simple copy with watermark metadata
            // In production, you would use a proper image manipulation library
            $watermarkedPath = $this->generateWatermarkedPath($filePath);
            $watermarkedFullPath = storage_path('app/' . $watermarkedPath);

            // Ensure directory exists
            $directory = dirname($watermarkedFullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Copy file (in production, add actual watermark here)
            copy($fullPath, $watermarkedFullPath);

            return $watermarkedPath;
        } catch (Exception $e) {
            Log::error('Image watermarking failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add watermark to PDF files (simplified version)
     */
    public function addPdfWatermark(string $filePath, string $watermarkText, array $options = []): string
    {
        try {
            $fullPath = storage_path('app/' . $filePath);

            // For now, create a simple copy with watermark metadata
            // In production, you would use a proper PDF manipulation library
            $watermarkedPath = $this->generateWatermarkedPath($filePath);
            $watermarkedFullPath = storage_path('app/' . $watermarkedPath);

            // Ensure directory exists
            $directory = dirname($watermarkedFullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Copy file (in production, add actual watermark here)
            copy($fullPath, $watermarkedFullPath);

            return $watermarkedPath;
        } catch (Exception $e) {
            Log::error('PDF watermarking failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create watermark copy for unsupported file types
     */
    public function createWatermarkCopy(string $filePath, string $watermarkText): string
    {
        $watermarkedPath = $this->generateWatermarkedPath($filePath);
        $watermarkedFullPath = storage_path('app/' . $watermarkedPath);

        // Ensure directory exists
        $directory = dirname($watermarkedFullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Copy original file
        copy(storage_path('app/' . $filePath), $watermarkedFullPath);

        return $watermarkedPath;
    }

    /**
     * Create watermark record in database
     */
    public function createWatermarkRecord(int $originalFileId, string $watermarkedPath, string $watermarkText, array $settings = []): FileWatermark
    {
        $fileSize = Storage::size($watermarkedPath);
        $checksum = md5_file(storage_path('app/' . $watermarkedPath));

        return FileWatermark::create([
            'original_file_id' => $originalFileId,
            'watermarked_path' => $watermarkedPath,
            'watermark_text' => $watermarkText,
            'watermark_type' => 'text',
            'watermark_settings' => $settings,
            'file_size' => $fileSize,
            'checksum' => $checksum
        ]);
    }

    /**
     * Generate default watermark text
     */
    public function generateDefaultWatermark(?int $userId = null): string
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $user = $userId ? "User {$userId}" : 'DDS Portal';

        return "DDS Portal - {$user} - {$timestamp}";
    }

    /**
     * Generate custom watermark with user context
     */
    public function generateCustomWatermark(int $userId, ?string $context = null): string
    {
        $user = Auth::user();
        $userName = $user?->name ?? "User {$userId}";
        $department = $user?->department?->name ?? 'Unknown Dept';
        $timestamp = now()->format('d/m/Y H:i');

        $watermark = "DDS Portal - {$userName} ({$department}) - {$timestamp}";

        if ($context) {
            $watermark .= " - {$context}";
        }

        return $watermark;
    }

    /**
     * Get watermarked file path for an original file
     */
    public function getWatermarkedPath(string $originalPath): ?string
    {
        $attachment = InvoiceAttachment::where('file_path', $originalPath)->first();

        if (!$attachment || !$attachment->watermark) {
            return null;
        }

        return $attachment->watermark->watermarked_path;
    }

    /**
     * Check if file has watermark
     */
    public function hasWatermark(int $fileId): bool
    {
        return FileWatermark::where('original_file_id', $fileId)->exists();
    }

    /**
     * Remove watermark files and records
     */
    public function removeWatermark(int $fileId): bool
    {
        $watermarks = FileWatermark::where('original_file_id', $fileId)->get();

        foreach ($watermarks as $watermark) {
            if ($watermark->exists()) {
                Storage::delete($watermark->watermarked_path);
            }
            $watermark->delete();
        }

        return true;
    }

    /**
     * Generate watermarked file path
     */
    private function generateWatermarkedPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        $watermarkedFilename = "{$filename}_watermarked_" . time() . ".{$extension}";

        return "{$directory}/watermarked/{$watermarkedFilename}";
    }
}
