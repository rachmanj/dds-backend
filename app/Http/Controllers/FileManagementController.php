<?php

namespace App\Http\Controllers;

use App\Models\InvoiceAttachment;
use App\Models\FileWatermark;
use App\Models\FileProcessingJob;
use App\Services\WatermarkService;
use App\Services\FileProcessingService;
use App\Jobs\ProcessFileWatermark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FileManagementController extends Controller
{
    private WatermarkService $watermarkService;
    private FileProcessingService $fileProcessingService;

    public function __construct(
        WatermarkService $watermarkService,
        FileProcessingService $fileProcessingService
    ) {
        $this->watermarkService = $watermarkService;
        $this->fileProcessingService = $fileProcessingService;
    }

    /**
     * Upload file with enhanced processing options
     */
    public function upload(Request $request, int $invoiceId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'watermark_enabled' => 'boolean',
            'watermark_text' => 'nullable|string|max:100',
            'watermark_position' => ['nullable', Rule::in(['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'])],
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $watermarkEnabled = $request->boolean('watermark_enabled', true);

            // Process the upload
            $attachment = $this->fileProcessingService->processUploadedFile(
                $file,
                $invoiceId,
                $watermarkEnabled
            );

            // Update description if provided
            if ($request->filled('description')) {
                $attachment->update(['description' => $request->description]);
            }

            return response()->json([
                'success' => true,
                'attachment' => [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_size' => $attachment->file_size,
                    'formatted_file_size' => $attachment->formatted_file_size,
                    'mime_type' => $attachment->mime_type,
                    'is_image' => $attachment->is_image,
                    'is_pdf' => $attachment->is_pdf,
                    'watermark_enabled' => $watermarkEnabled,
                    'processing_status' => 'pending'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file processing status
     */
    public function getProcessingStatus(int $attachmentId): JsonResponse
    {
        try {
            $status = $this->fileProcessingService->getProcessingStatus($attachmentId);

            return response()->json([
                'success' => true,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get processing status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file with watermark option
     */
    public function download(Request $request, int $invoiceId, int $attachmentId): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $attachment = InvoiceAttachment::where('invoice_id', $invoiceId)
            ->findOrFail($attachmentId);

        $watermarked = $request->boolean('watermarked', false);

        if ($watermarked && $attachment->watermark) {
            $filePath = storage_path('app/' . $attachment->watermark->watermarked_path);
            $fileName = pathinfo($attachment->file_name, PATHINFO_FILENAME) . '_watermarked.' .
                pathinfo($attachment->file_name, PATHINFO_EXTENSION);
        } else {
            $filePath = storage_path('app/attachments/' . $attachment->file_path);
            $fileName = $attachment->file_name;
        }

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $fileName);
    }

    /**
     * Apply watermark to existing file
     */
    public function applyWatermark(Request $request, int $attachmentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'watermark_text' => 'nullable|string|max:100',
            'watermark_position' => ['nullable', Rule::in(['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'])],
            'force' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attachment = InvoiceAttachment::findOrFail($attachmentId);

            // Check if watermark already exists
            if (!$request->boolean('force') && $this->watermarkService->hasWatermark($attachmentId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Watermark already exists. Use force=true to recreate.'
                ], 409);
            }

            // Remove existing watermark if forcing
            if ($request->boolean('force')) {
                $this->watermarkService->removeWatermark($attachmentId);
            }

            // Queue watermarking job
            $options = [];
            if ($request->filled('watermark_position')) {
                $options['position'] = $request->watermark_position;
            }

            $job = $this->fileProcessingService->queueWatermarking($attachmentId, $options);

            // Dispatch the job
            ProcessFileWatermark::dispatch($attachmentId, $options);

            return response()->json([
                'success' => true,
                'message' => 'Watermarking job queued successfully',
                'job_id' => $job->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply watermark: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove watermark from file
     */
    public function removeWatermark(int $attachmentId): JsonResponse
    {
        try {
            $attachment = InvoiceAttachment::findOrFail($attachmentId);

            if (!$this->watermarkService->hasWatermark($attachmentId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No watermark found for this file'
                ], 404);
            }

            $this->watermarkService->removeWatermark($attachmentId);

            return response()->json([
                'success' => true,
                'message' => 'Watermark removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove watermark: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get watermark details
     */
    public function getWatermarkDetails(int $attachmentId): JsonResponse
    {
        try {
            $watermark = FileWatermark::where('original_file_id', $attachmentId)->first();

            if (!$watermark) {
                return response()->json([
                    'success' => false,
                    'message' => 'No watermark found for this file'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'watermark' => [
                    'id' => $watermark->id,
                    'watermark_text' => $watermark->watermark_text,
                    'watermark_type' => $watermark->watermark_type,
                    'watermark_settings' => $watermark->watermark_settings,
                    'file_size' => $watermark->file_size,
                    'human_size' => $watermark->human_size,
                    'checksum' => $watermark->checksum,
                    'created_at' => $watermark->created_at,
                    'exists' => $watermark->exists()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get watermark details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file processing jobs
     */
    public function getProcessingJobs(Request $request): JsonResponse
    {
        $query = FileProcessingJob::with('file');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('file_id')) {
            $query->where('file_id', $request->file_id);
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'jobs' => $jobs
        ]);
    }

    /**
     * Retry failed processing job
     */
    public function retryProcessingJob(int $jobId): JsonResponse
    {
        try {
            $job = FileProcessingJob::findOrFail($jobId);

            if (!$job->canRetry()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job cannot be retried (too many attempts or not failed)'
                ], 409);
            }

            // Reset job status
            $job->update([
                'status' => FileProcessingJob::STATUS_PENDING,
                'error_message' => null
            ]);

            // Dispatch new job
            if ($job->job_type === FileProcessingJob::TYPE_WATERMARK) {
                ProcessFileWatermark::dispatch($job->file_id, $job->job_parameters ?? []);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job retry queued successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file statistics
     */
    public function getFileStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_files' => InvoiceAttachment::count(),
                'total_size' => InvoiceAttachment::sum('file_size'),
                'watermarked_files' => FileWatermark::count(),
                'processing_jobs' => [
                    'pending' => FileProcessingJob::where('status', FileProcessingJob::STATUS_PENDING)->count(),
                    'processing' => FileProcessingJob::where('status', FileProcessingJob::STATUS_PROCESSING)->count(),
                    'completed' => FileProcessingJob::where('status', FileProcessingJob::STATUS_COMPLETED)->count(),
                    'failed' => FileProcessingJob::where('status', FileProcessingJob::STATUS_FAILED)->count(),
                ],
                'file_types' => InvoiceAttachment::selectRaw('mime_type, COUNT(*) as count')
                    ->groupBy('mime_type')
                    ->orderBy('count', 'desc')
                    ->get()
            ];

            // Format total size
            $size = $stats['total_size'];
            $units = ['B', 'KB', 'MB', 'GB'];
            $unitIndex = 0;

            while ($size >= 1024 && $unitIndex < count($units) - 1) {
                $size /= 1024;
                $unitIndex++;
            }

            $stats['total_size_formatted'] = round($size, 2) . ' ' . $units[$unitIndex];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
