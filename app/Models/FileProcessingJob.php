<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileProcessingJob extends Model
{
    protected $fillable = [
        'file_id',
        'job_type',
        'status',
        'attempts',
        'error_message',
        'job_parameters',
        'result_data',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'job_parameters' => 'array',
        'result_data' => 'array',
        'attempts' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    public $timestamps = false;

    // Job types constants
    const TYPE_WATERMARK = 'watermark';
    const TYPE_THUMBNAIL = 'thumbnail';
    const TYPE_COMPRESS = 'compress';
    const TYPE_VIRUS_SCAN = 'virus_scan';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the file that this job is processing
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(InvoiceAttachment::class, 'file_id');
    }

    /**
     * Mark the job as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'attempts' => $this->attempts + 1
        ]);
    }

    /**
     * Mark the job as completed
     */
    public function markAsCompleted(array $resultData = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'result_data' => $resultData,
            'error_message' => null
        ]);
    }

    /**
     * Mark the job as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now()
        ]);
    }

    /**
     * Check if job can be retried
     */
    public function canRetry(int $maxAttempts = 3): bool
    {
        return $this->status === self::STATUS_FAILED && $this->attempts < $maxAttempts;
    }

    /**
     * Get the processing duration in seconds
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get human readable duration
     */
    public function getHumanDurationAttribute(): string
    {
        $duration = $this->duration;

        if ($duration === null) {
            return 'N/A';
        }

        if ($duration < 60) {
            return $duration . 's';
        }

        return gmdate('H:i:s', $duration);
    }

    /**
     * Scope to get jobs by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get jobs by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('job_type', $type);
    }

    /**
     * Scope to get pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get failed jobs that can be retried
     */
    public function scopeRetryable($query, int $maxAttempts = 3)
    {
        return $query->where('status', self::STATUS_FAILED)
            ->where('attempts', '<', $maxAttempts);
    }

    /**
     * Scope to get stuck processing jobs (processing for more than X minutes)
     */
    public function scopeStuck($query, int $minutes = 30)
    {
        return $query->where('status', self::STATUS_PROCESSING)
            ->where('started_at', '<', now()->subMinutes($minutes));
    }
}
