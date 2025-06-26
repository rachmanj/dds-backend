<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_id',
        'location_code',
        'moved_by',
        'moved_at',
        'distribution_id',
        'reason',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    const UPDATED_AT = null; // We only track creation/movement time

    /**
     * Get the document that this location entry belongs to (polymorphic)
     */
    public function document(): MorphTo
    {
        return $this->morphTo('document', 'document_type', 'document_id');
    }

    /**
     * Get the user who moved the document
     */
    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    /**
     * Get the distribution that caused this movement
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Get the department for this location
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'location_code', 'location_code');
    }

    /**
     * Scope: Get location history for a specific document
     */
    public function scopeForDocument($query, string $documentType, int $documentId)
    {
        return $query->where('document_type', $documentType)
            ->where('document_id', $documentId);
    }

    /**
     * Scope: Get documents in a specific location
     */
    public function scopeInLocation($query, string $locationCode)
    {
        return $query->where('location_code', $locationCode);
    }

    /**
     * Scope: Get recent movements
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('moved_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Order by movement time
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('moved_at', 'desc');
    }

    /**
     * Get the current location for a document
     */
    public static function getCurrentLocation(string $documentType, int $documentId): ?string
    {
        $location = static::forDocument($documentType, $documentId)
            ->latest()
            ->first();

        return $location?->location_code;
    }

    /**
     * Track a document movement
     */
    public static function trackMovement(
        string $documentType,
        int $documentId,
        string $toLocation,
        ?int $movedBy = null,
        ?int $distributionId = null,
        ?string $reason = null
    ): self {
        return static::create([
            'document_type' => $documentType,
            'document_id' => $documentId,
            'location_code' => $toLocation,
            'moved_by' => $movedBy,
            'distribution_id' => $distributionId,
            'reason' => $reason,
            'moved_at' => now(),
        ]);
    }

    /**
     * Get location history for a document
     */
    public static function getLocationHistory(string $documentType, int $documentId)
    {
        return static::forDocument($documentType, $documentId)
            ->with(['movedBy:id,name', 'distribution:id,distribution_number', 'department:location_code,name'])
            ->latest()
            ->get();
    }
}
