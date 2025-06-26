<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FileWatermark extends Model
{
    protected $fillable = [
        'original_file_id',
        'watermarked_path',
        'watermark_text',
        'watermark_type',
        'watermark_settings',
        'file_size',
        'checksum'
    ];

    protected $casts = [
        'watermark_settings' => 'array',
        'file_size' => 'integer',
        'created_at' => 'datetime'
    ];

    public $timestamps = false;

    /**
     * Get the original file (invoice attachment) that was watermarked
     */
    public function originalFile(): BelongsTo
    {
        return $this->belongsTo(InvoiceAttachment::class, 'original_file_id');
    }

    /**
     * Get the full path to the watermarked file
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->watermarked_path);
    }

    /**
     * Get the public URL for the watermarked file
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->watermarked_path);
    }

    /**
     * Check if the watermarked file exists
     */
    public function exists(): bool
    {
        return Storage::exists($this->watermarked_path);
    }

    /**
     * Get the file size in human readable format
     */
    public function getHumanSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get watermarks by file type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('watermark_type', $type);
    }

    /**
     * Get recent watermarks
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Delete the watermarked file from storage when model is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($watermark) {
            if ($watermark->exists()) {
                Storage::delete($watermark->watermarked_path);
            }
        });
    }
}
