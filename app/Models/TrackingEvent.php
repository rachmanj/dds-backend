<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TrackingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'trackable_type',
        'trackable_id',
        'event_type',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    const UPDATED_AT = null; // We only track creation time

    /**
     * Get the trackable entity (polymorphic)
     */
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who triggered this event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get events for a specific trackable entity
     */
    public function scopeForTrackable($query, string $trackableType, int $trackableId)
    {
        return $query->where('trackable_type', $trackableType)
            ->where('trackable_id', $trackableId);
    }

    /**
     * Scope: Get events of a specific type
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope: Get events by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get recent events
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Order by most recent
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Log a tracking event
     */
    public static function log(
        Model $trackable,
        string $eventType,
        ?int $userId = null,
        array $metadata = []
    ): self {
        return static::create([
            'trackable_type' => get_class($trackable),
            'trackable_id' => $trackable->id,
            'event_type' => $eventType,
            'user_id' => $userId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get timeline for a trackable entity
     */
    public static function getTimeline(Model $trackable, int $limit = 50)
    {
        return static::forTrackable(get_class($trackable), $trackable->id)
            ->with('user:id,name')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get user activity summary
     */
    public static function getUserActivity(int $userId, int $days = 30)
    {
        return static::byUser($userId)
            ->recent($days)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get();
    }

    /**
     * Get system activity summary
     */
    public static function getSystemActivity(int $days = 7)
    {
        return static::recent($days)
            ->selectRaw('DATE(created_at) as date, event_type, COUNT(*) as count')
            ->groupBy('date', 'event_type')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Available event types
     */
    public static function getEventTypes(): array
    {
        return [
            'document_created' => 'Document Created',
            'document_updated' => 'Document Updated',
            'document_moved' => 'Document Moved',
            'document_deleted' => 'Document Deleted',
            'distribution_created' => 'Distribution Created',
            'distribution_sent' => 'Distribution Sent',
            'distribution_received' => 'Distribution Received',
            'distribution_verified' => 'Distribution Verified',
            'distribution_completed' => 'Distribution Completed',
            'user_login' => 'User Login',
            'user_logout' => 'User Logout',
            'permission_changed' => 'Permission Changed',
            'system_error' => 'System Error',
            'security_event' => 'Security Event',
        ];
    }
}
