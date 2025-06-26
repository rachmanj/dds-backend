<?php

namespace App\Jobs;

use App\Models\FileProcessingJob;
use App\Models\InvoiceAttachment;
use App\Services\WatermarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessFileWatermark implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $attachmentId;
    public array $options;
    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 120; // 2 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(int $attachmentId, array $options = [])
    {
        $this->attachmentId = $attachmentId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(WatermarkService $watermarkService): void
    {
        try {
            // Find the attachment
            $attachment = InvoiceAttachment::findOrFail($this->attachmentId);

            // Find or create processing job record
            $job = FileProcessingJob::firstOrCreate([
                'file_id' => $this->attachmentId,
                'job_type' => FileProcessingJob::TYPE_WATERMARK,
                'status' => FileProcessingJob::STATUS_PENDING
            ], [
                'job_parameters' => $this->options
            ]);

            // Mark as started
            $job->markAsStarted();

            // Generate watermark text with user context
            $watermarkText = $watermarkService->generateCustomWatermark(
                $attachment->uploaded_by,
                "Invoice #{$attachment->invoice_id}"
            );

            // Apply watermark
            $watermarkedPath = $watermarkService->addWatermark(
                $attachment->file_path,
                $watermarkText,
                $this->options
            );

            // Create watermark record
            $watermarkRecord = $watermarkService->createWatermarkRecord(
                $attachment->id,
                $watermarkedPath,
                $watermarkText,
                $this->options
            );

            // Mark job as completed
            $job->markAsCompleted([
                'watermark_id' => $watermarkRecord->id,
                'watermarked_path' => $watermarkedPath,
                'watermark_text' => $watermarkText,
                'file_size' => $watermarkRecord->file_size
            ]);

            Log::info('File watermark processed successfully', [
                'attachment_id' => $this->attachmentId,
                'watermark_id' => $watermarkRecord->id,
                'watermarked_path' => $watermarkedPath
            ]);
        } catch (Exception $e) {
            // Find the job record and mark as failed
            $job = FileProcessingJob::where('file_id', $this->attachmentId)
                ->where('job_type', FileProcessingJob::TYPE_WATERMARK)
                ->first();

            if ($job) {
                $job->markAsFailed($e->getMessage());
            }

            Log::error('File watermark processing failed', [
                'attachment_id' => $this->attachmentId,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('File watermark job failed permanently', [
            'attachment_id' => $this->attachmentId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark job as permanently failed
        $job = FileProcessingJob::where('file_id', $this->attachmentId)
            ->where('job_type', FileProcessingJob::TYPE_WATERMARK)
            ->first();

        if ($job) {
            $job->markAsFailed("Job failed after {$this->tries} attempts: " . $exception->getMessage());
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['watermark', 'file-processing', "attachment:{$this->attachmentId}"];
    }
}
