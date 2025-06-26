<?php

namespace App\Services;

use App\Models\FileProcessingJob;
use App\Models\InvoiceAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Exception;

class FileProcessingService
{
    private WatermarkService $watermarkService;

    public function __construct(WatermarkService $watermarkService)
    {
        $this->watermarkService = $watermarkService;
    }

    /**
     * Process uploaded file with optional watermarking
     */
    public function processUploadedFile(UploadedFile $file, int $invoiceId, bool $enableWatermark = true): InvoiceAttachment
    {
        try {
            // Store the file
            $filePath = $file->store("invoices/{$invoiceId}/attachments", 'attachments');

            // Create attachment record
            $attachment = InvoiceAttachment::create([
                'invoice_id' => $invoiceId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => Auth::id()
            ]);

            // Queue processing jobs
            if ($enableWatermark) {
                $this->queueWatermarking($attachment->id);
            }

            return $attachment;
        } catch (Exception $e) {
            Log::error('File processing failed', [
                'invoice_id' => $invoiceId,
                'file_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Queue watermarking job
     */
    public function queueWatermarking(int $attachmentId, array $options = []): FileProcessingJob
    {
        return FileProcessingJob::create([
            'file_id' => $attachmentId,
            'job_type' => FileProcessingJob::TYPE_WATERMARK,
            'status' => FileProcessingJob::STATUS_PENDING,
            'job_parameters' => $options
        ]);
    }

    /**
     * Process watermarking queue
     */
    public function processWatermarkingQueue(int $limit = 10): int
    {
        $pendingJobs = FileProcessingJob::pending()
            ->byType(FileProcessingJob::TYPE_WATERMARK)
            ->limit($limit)
            ->get();

        $processedCount = 0;

        foreach ($pendingJobs as $job) {
            try {
                $this->processWatermarkJob($job);
                $processedCount++;
            } catch (Exception $e) {
                Log::error('Watermark job processing failed', [
                    'job_id' => $job->id,
                    'file_id' => $job->file_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processedCount;
    }

    /**
     * Process individual watermark job
     */
    public function processWatermarkJob(FileProcessingJob $job): void
    {
        $job->markAsStarted();

        try {
            $attachment = $job->file;

            if (!$attachment) {
                throw new Exception("Attachment not found for job {$job->id}");
            }

            // Generate watermark text
            $watermarkText = $this->watermarkService->generateCustomWatermark(
                $attachment->uploaded_by,
                "Invoice #{$attachment->invoice_id}"
            );

            // Apply watermark
            $watermarkedPath = $this->watermarkService->addWatermark(
                $attachment->file_path,
                $watermarkText,
                $job->job_parameters ?? []
            );

            // Create watermark record
            $watermarkRecord = $this->watermarkService->createWatermarkRecord(
                $attachment->id,
                $watermarkedPath,
                $watermarkText,
                $job->job_parameters ?? []
            );

            // Mark job as completed
            $job->markAsCompleted([
                'watermark_id' => $watermarkRecord->id,
                'watermarked_path' => $watermarkedPath,
                'watermark_text' => $watermarkText
            ]);
        } catch (Exception $e) {
            $job->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Get file processing status
     */
    public function getProcessingStatus(int $attachmentId): array
    {
        $jobs = FileProcessingJob::where('file_id', $attachmentId)->get();

        $status = [
            'attachment_id' => $attachmentId,
            'overall_status' => 'pending',
            'jobs' => []
        ];

        foreach ($jobs as $job) {
            $status['jobs'][] = [
                'type' => $job->job_type,
                'status' => $job->status,
                'attempts' => $job->attempts,
                'error' => $job->error_message,
                'duration' => $job->human_duration,
                'created_at' => $job->created_at,
                'completed_at' => $job->completed_at
            ];
        }

        // Determine overall status
        $statuses = $jobs->pluck('status')->unique();

        if ($statuses->contains(FileProcessingJob::STATUS_FAILED)) {
            $status['overall_status'] = 'failed';
        } elseif ($statuses->contains(FileProcessingJob::STATUS_PROCESSING)) {
            $status['overall_status'] = 'processing';
        } elseif ($statuses->every(fn($s) => $s === FileProcessingJob::STATUS_COMPLETED)) {
            $status['overall_status'] = 'completed';
        }

        return $status;
    }
}
