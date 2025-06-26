<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_type',
        'entity_type',
        'entity_id',
        'duration_seconds',
        'metadata',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'metadata' => 'array',
        'duration_seconds' => 'integer',
    ];

    const UPDATED_AT = null; // We only track creation time

    // Activity types constants
    const ACTIVITY_LOGIN = 'login';
    const ACTIVITY_LOGOUT = 'logout';
    const ACTIVITY_DISTRIBUTION_CREATE = 'distribution_create';
    const ACTIVITY_DISTRIBUTION_VERIFY = 'distribution_verify';
    const ACTIVITY_DISTRIBUTION_SEND = 'distribution_send';
    const ACTIVITY_DISTRIBUTION_RECEIVE = 'distribution_receive';
    const ACTIVITY_DISTRIBUTION_COMPLETE = 'distribution_complete';
    const ACTIVITY_INVOICE_CREATE = 'invoice_create';
    const ACTIVITY_INVOICE_UPDATE = 'invoice_update';
    const ACTIVITY_DOCUMENT_UPLOAD = 'document_upload';
    const ACTIVITY_DOCUMENT_DOWNLOAD = 'document_download';
    const ACTIVITY_DASHBOARD_VIEW = 'dashboard_view';
    const ACTIVITY_REPORT_GENERATE = 'report_generate';

    /**
     * Relationship: User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by activity type
     */
    public function scopeOfType($query, string $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by entity
     */
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    /**
     * Scope: Recent activities
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope: Today's activities
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope: This week's activities
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Log user activity
     */
    public static function logActivity(
        int $userId,
        string $activityType,
        ?string $entityType = null,
        ?int $entityId = null,
        ?int $durationSeconds = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'duration_seconds' => $durationSeconds,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get activity summary for user
     */
    public static function getUserActivitySummary(int $userId, int $days = 30): array
    {
        $activities = static::forUser($userId)
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total_activities' => $activities->count(),
            'unique_days_active' => $activities->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })->count(),
            'activity_breakdown' => [],
            'recent_activities' => [],
            'most_active_day' => null,
            'avg_session_duration' => 0,
        ];

        // Activity breakdown by type
        $breakdown = $activities->groupBy('activity_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'percentage' => 0, // Will be calculated below
                'avg_duration' => $group->whereNotNull('duration_seconds')->avg('duration_seconds'),
            ];
        });

        // Calculate percentages
        $totalCount = $activities->count();
        if ($totalCount > 0) {
            $breakdown = $breakdown->map(function ($item) use ($totalCount) {
                $item['percentage'] = round(($item['count'] / $totalCount) * 100, 1);
                return $item;
            });
        }

        $summary['activity_breakdown'] = $breakdown->toArray();

        // Recent activities (last 10)
        $summary['recent_activities'] = $activities->take(10)->map(function ($activity) {
            return [
                'activity_type' => $activity->activity_type,
                'entity_type' => $activity->entity_type,
                'entity_id' => $activity->entity_id,
                'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                'duration_seconds' => $activity->duration_seconds,
                'metadata' => $activity->metadata,
            ];
        })->toArray();

        // Most active day
        $dailyCount = $activities->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        })->map->count()->sortDesc();

        if ($dailyCount->isNotEmpty()) {
            $summary['most_active_day'] = [
                'date' => $dailyCount->keys()->first(),
                'activity_count' => $dailyCount->first(),
            ];
        }

        // Average session duration (for login activities)
        $sessionDurations = $activities->where('activity_type', static::ACTIVITY_LOGIN)
            ->whereNotNull('duration_seconds')
            ->pluck('duration_seconds');

        if ($sessionDurations->isNotEmpty()) {
            $summary['avg_session_duration'] = round($sessionDurations->avg());
        }

        return $summary;
    }

    /**
     * Get department activity metrics
     */
    public static function getDepartmentMetrics(int $departmentId, int $days = 30): array
    {
        $userIds = User::where('department_id', $departmentId)->pluck('id');

        $activities = static::whereIn('user_id', $userIds)
            ->recent($days)
            ->get();

        return [
            'total_activities' => $activities->count(),
            'unique_active_users' => $activities->pluck('user_id')->unique()->count(),
            'avg_daily_activities' => $activities->count() / $days,
            'activity_types' => $activities->groupBy('activity_type')->map->count()->toArray(),
            'most_active_users' => $activities->groupBy('user_id')
                ->map(function ($userActivities, $userId) {
                    $user = User::find($userId);
                    return [
                        'user_id' => $userId,
                        'user_name' => $user ? $user->name : 'Unknown',
                        'activity_count' => $userActivities->count(),
                    ];
                })
                ->sortByDesc('activity_count')
                ->take(5)
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Get system-wide activity statistics
     */
    public static function getSystemStatistics(int $days = 30): array
    {
        $activities = static::recent($days)->get();

        return [
            'total_activities' => $activities->count(),
            'unique_users' => $activities->pluck('user_id')->unique()->count(),
            'activities_by_type' => $activities->groupBy('activity_type')->map->count()->toArray(),
            'activities_by_day' => $activities->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })->map->count()->toArray(),
            'peak_activity_day' => $activities->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })->map->count()->sortDesc()->keys()->first(),
            'avg_daily_activities' => round($activities->count() / $days, 1),
        ];
    }

    /**
     * Clean up old activity records
     */
    public static function cleanupOldRecords(int $keepDays = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($keepDays);

        return static::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get formatted activity description
     */
    public function getDescriptionAttribute(): string
    {
        $descriptions = [
            static::ACTIVITY_LOGIN => 'Logged in',
            static::ACTIVITY_LOGOUT => 'Logged out',
            static::ACTIVITY_DISTRIBUTION_CREATE => 'Created distribution',
            static::ACTIVITY_DISTRIBUTION_VERIFY => 'Verified distribution',
            static::ACTIVITY_DISTRIBUTION_SEND => 'Sent distribution',
            static::ACTIVITY_DISTRIBUTION_RECEIVE => 'Received distribution',
            static::ACTIVITY_DISTRIBUTION_COMPLETE => 'Completed distribution',
            static::ACTIVITY_INVOICE_CREATE => 'Created invoice',
            static::ACTIVITY_INVOICE_UPDATE => 'Updated invoice',
            static::ACTIVITY_DOCUMENT_UPLOAD => 'Uploaded document',
            static::ACTIVITY_DOCUMENT_DOWNLOAD => 'Downloaded document',
            static::ACTIVITY_DASHBOARD_VIEW => 'Viewed dashboard',
            static::ACTIVITY_REPORT_GENERATE => 'Generated report',
        ];

        return $descriptions[$this->activity_type] ?? 'Unknown activity';
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }
}
