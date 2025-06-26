<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WeeklyAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_start',
        'total_distributions',
        'completed_distributions',
        'avg_completion_hours',
        'active_users',
        'department_stats',
        'performance_metrics',
    ];

    protected $casts = [
        'week_start' => 'date',
        'created_at' => 'datetime',
        'department_stats' => 'array',
        'performance_metrics' => 'array',
        'avg_completion_hours' => 'decimal:1',
    ];

    const UPDATED_AT = null; // We only track creation time

    /**
     * Scope: Get analytics for a specific time period
     */
    public function scopeForPeriod($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('week_start', [$startDate->startOfWeek(), $endDate->startOfWeek()]);
    }

    /**
     * Scope: Get recent analytics
     */
    public function scopeRecent($query, int $weeks = 12)
    {
        $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
        return $query->where('week_start', '>=', $startDate)->orderBy('week_start', 'desc');
    }

    /**
     * Scope: Order by week
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('week_start', 'desc');
    }

    /**
     * Get completion rate percentage
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->total_distributions === 0) {
            return 0.0;
        }

        return round(($this->completed_distributions / $this->total_distributions) * 100, 1);
    }

    /**
     * Get efficiency score based on completion rate and time
     */
    public function getEfficiencyScoreAttribute(): float
    {
        $completionRate = $this->completion_rate;

        // Penalty for longer completion times (target: 24 hours)
        $timePenalty = $this->avg_completion_hours > 24
            ? (1 - ($this->avg_completion_hours - 24) / 48)
            : 1.0;

        $timePenalty = max(0.1, $timePenalty); // Minimum 10%

        return round($completionRate * $timePenalty, 1);
    }

    /**
     * Get formatted week range
     */
    public function getWeekRangeAttribute(): string
    {
        $weekStart = $this->week_start;
        $weekEnd = $weekStart->copy()->endOfWeek();

        return $weekStart->format('M j') . ' - ' . $weekEnd->format('M j, Y');
    }

    /**
     * Create or update analytics for a specific week
     */
    public static function updateForWeek(Carbon $weekStart, array $data): self
    {
        $weekStart = $weekStart->copy()->startOfWeek();

        return static::updateOrCreate(
            ['week_start' => $weekStart],
            $data
        );
    }

    /**
     * Get analytics summary for dashboard
     */
    public static function getDashboardSummary(int $weeks = 12): array
    {
        $analytics = static::recent($weeks)->get();

        if ($analytics->isEmpty()) {
            return [
                'total_distributions' => 0,
                'total_completed' => 0,
                'avg_completion_rate' => 0,
                'avg_completion_hours' => 0,
                'total_active_users' => 0,
                'trend_data' => [],
                'department_breakdown' => [],
            ];
        }

        $totalDistributions = $analytics->sum('total_distributions');
        $totalCompleted = $analytics->sum('completed_distributions');
        $avgCompletionRate = $totalDistributions > 0 ? round(($totalCompleted / $totalDistributions) * 100, 1) : 0;

        // Weighted average for completion hours
        $weightedHours = $analytics->sum(function ($item) {
            return $item->avg_completion_hours * $item->completed_distributions;
        });
        $avgCompletionHours = $totalCompleted > 0 ? round($weightedHours / $totalCompleted, 1) : 0;

        // Trend data for charts
        $trendData = $analytics->map(function ($item) {
            return [
                'week' => $item->week_range,
                'week_start' => $item->week_start->format('Y-m-d'),
                'distributions' => $item->total_distributions,
                'completed' => $item->completed_distributions,
                'completion_rate' => $item->completion_rate,
                'avg_hours' => $item->avg_completion_hours,
                'efficiency_score' => $item->efficiency_score,
            ];
        })->reverse()->values();

        // Department breakdown from latest week
        $latestWeek = $analytics->first();
        $departmentBreakdown = $latestWeek->department_stats ?? [];

        return [
            'total_distributions' => $totalDistributions,
            'total_completed' => $totalCompleted,
            'avg_completion_rate' => $avgCompletionRate,
            'avg_completion_hours' => $avgCompletionHours,
            'total_active_users' => $analytics->max('active_users'),
            'trend_data' => $trendData,
            'department_breakdown' => $departmentBreakdown,
            'weeks_analyzed' => $analytics->count(),
        ];
    }

    /**
     * Get performance metrics comparison
     */
    public static function getPerformanceComparison(): array
    {
        $currentWeek = static::where('week_start', Carbon::now()->startOfWeek())->first();
        $previousWeek = static::where('week_start', Carbon::now()->subWeek()->startOfWeek())->first();

        if (!$currentWeek || !$previousWeek) {
            return [
                'distributions_change' => 0,
                'completion_rate_change' => 0,
                'avg_hours_change' => 0,
                'users_change' => 0,
            ];
        }

        return [
            'distributions_change' => static::calculatePercentageChange(
                $previousWeek->total_distributions,
                $currentWeek->total_distributions
            ),
            'completion_rate_change' => round(
                $currentWeek->completion_rate - $previousWeek->completion_rate,
                1
            ),
            'avg_hours_change' => round(
                $currentWeek->avg_completion_hours - $previousWeek->avg_completion_hours,
                1
            ),
            'users_change' => $currentWeek->active_users - $previousWeek->active_users,
        ];
    }

    /**
     * Calculate percentage change
     */
    private static function calculatePercentageChange($oldValue, $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100.0 : 0.0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }
}
