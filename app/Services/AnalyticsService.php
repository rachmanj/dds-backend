<?php

namespace App\Services;

use App\Models\WeeklyAnalytics;
use App\Models\UserActivity;
use App\Models\Distribution;
use App\Models\User;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    /**
     * Get dashboard metrics for a user
     */
    public function getDashboardMetrics(int $userId): array
    {
        $cacheKey = "dashboard_metrics_{$userId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId) { // 1 hour cache
            $user = User::with('department')->findOrFail($userId);

            // Get weekly analytics summary
            $weeklyAnalytics = WeeklyAnalytics::getDashboardSummary(12);

            // Get user-specific metrics
            $userMetrics = $this->getUserMetrics($userId);

            // Get department metrics
            $departmentMetrics = $this->getDepartmentMetrics($user->department_id ?? 0);

            // Get current week performance
            $currentWeekPerformance = $this->getCurrentWeekPerformance();

            return [
                'weekly_analytics' => $weeklyAnalytics,
                'user_metrics' => $userMetrics,
                'department_metrics' => $departmentMetrics,
                'current_week_performance' => $currentWeekPerformance,
                'last_updated' => Carbon::now()->toISOString(),
            ];
        });
    }

    /**
     * Get distribution metrics for a department
     */
    public function getDistributionMetrics(int $departmentId, array $dateRange = []): array
    {
        $startDate = isset($dateRange['start'])
            ? Carbon::parse($dateRange['start'])
            : Carbon::now()->subDays(30);
        $endDate = isset($dateRange['end'])
            ? Carbon::parse($dateRange['end'])
            : Carbon::now();

        $query = Distribution::where(function ($q) use ($departmentId) {
            $q->where('origin_department_id', $departmentId)
                ->orWhere('destination_department_id', $departmentId);
        })->whereBetween('created_at', [$startDate, $endDate]);

        $distributions = $query->get();

        return [
            'total_distributions' => $distributions->count(),
            'status_breakdown' => $distributions->groupBy('status')->map->count(),
            'completion_rate' => $this->calculateCompletionRate($distributions),
            'avg_completion_time' => $this->calculateAverageCompletionTime($distributions),
            'daily_distribution_count' => $this->getDailyDistributionCount($distributions),
            'type_breakdown' => $this->getDistributionTypeBreakdown($distributions),
            'department_flow' => $this->getDepartmentFlow($departmentId, $distributions),
        ];
    }

    /**
     * Get performance metrics for a department
     */
    public function getPerformanceMetrics(int $departmentId, string $period = '30d'): array
    {
        $days = $this->parsePeriodToDays($period);
        $startDate = Carbon::now()->subDays($days);

        return [
            'distribution_performance' => $this->getDistributionPerformance($departmentId, $startDate),
            'user_activity' => UserActivity::getDepartmentMetrics($departmentId, $days),
            'efficiency_trends' => $this->getEfficiencyTrends($departmentId, $days),
            'bottleneck_analysis' => $this->getBottleneckAnalysis($departmentId, $startDate),
        ];
    }

    /**
     * Get user activity metrics
     */
    public function getUserActivityMetrics(int $userId, string $period = '30d'): array
    {
        $days = $this->parsePeriodToDays($period);

        return UserActivity::getUserActivitySummary($userId, $days);
    }

    /**
     * Generate weekly analytics report
     */
    public function generateWeeklyReport(Carbon $weekStart): array
    {
        $weekStart = $weekStart->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Get distributions for this week
        $distributions = Distribution::whereBetween('created_at', [$weekStart, $weekEnd])->get();
        $completedDistributions = $distributions->where('status', 'completed');

        // Calculate metrics
        $totalDistributions = $distributions->count();
        $completedCount = $completedDistributions->count();
        $avgCompletionHours = $this->calculateAverageCompletionTime($completedDistributions);

        // Get active users for this week
        $activeUsers = UserActivity::whereBetween('created_at', [$weekStart, $weekEnd])
            ->distinct('user_id')
            ->count('user_id');

        // Department statistics
        $departmentStats = $this->generateDepartmentStats($distributions);

        // Performance metrics
        $performanceMetrics = [
            'completion_rate' => $totalDistributions > 0 ? round(($completedCount / $totalDistributions) * 100, 1) : 0,
            'avg_verification_time' => $this->calculateAverageVerificationTime($distributions),
            'bottlenecks' => $this->identifyWeeklyBottlenecks($distributions),
            'top_performers' => $this->getTopPerformers($weekStart, $weekEnd),
        ];

        return [
            'week_start' => $weekStart->format('Y-m-d'),
            'total_distributions' => $totalDistributions,
            'completed_distributions' => $completedCount,
            'avg_completion_hours' => $avgCompletionHours,
            'active_users' => $activeUsers,
            'department_stats' => $departmentStats,
            'performance_metrics' => $performanceMetrics,
        ];
    }

    /**
     * Get real-time dashboard data
     */
    public function getRealTimeDashboardData(): array
    {
        return [
            'distributions_today' => Distribution::whereDate('created_at', Carbon::today())->count(),
            'distributions_this_week' => Distribution::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'pending_verifications' => Distribution::whereIn('status', ['sent', 'received'])->count(),
            'active_users_today' => UserActivity::today()->distinct('user_id')->count('user_id'),
            'recent_completions' => Distribution::where('status', 'completed')
                ->whereDate('updated_at', Carbon::today())
                ->count(),
            'system_health' => $this->getSystemHealthMetrics(),
        ];
    }

    /**
     * Private helper methods
     */

    private function getUserMetrics(int $userId): array
    {
        $distributions = Distribution::where('created_by', $userId)
            ->orWhereHas('histories', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with('histories')
            ->get();

        return [
            'total_distributions_created' => $distributions->where('created_by', $userId)->count(),
            'total_distributions_handled' => $distributions->count(),
            'completion_rate' => $this->calculateCompletionRate($distributions),
            'avg_handling_time' => $this->calculateUserHandlingTime($userId),
            'activity_summary' => UserActivity::getUserActivitySummary($userId, 7),
        ];
    }

    private function getDepartmentMetrics(int $departmentId): array
    {
        if ($departmentId === 0) {
            return [];
        }

        $distributions = Distribution::where('origin_department_id', $departmentId)
            ->orWhere('destination_department_id', $departmentId)
            ->get();

        return [
            'total_distributions' => $distributions->count(),
            'sent_distributions' => $distributions->where('origin_department_id', $departmentId)->count(),
            'received_distributions' => $distributions->where('destination_department_id', $departmentId)->count(),
            'completion_rate' => $this->calculateCompletionRate($distributions),
            'avg_processing_time' => $this->calculateAverageCompletionTime($distributions),
        ];
    }

    private function getCurrentWeekPerformance(): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        $distributions = Distribution::where('created_at', '>=', $weekStart)->get();

        return [
            'distributions_this_week' => $distributions->count(),
            'completed_this_week' => $distributions->where('status', 'completed')->count(),
            'pending_this_week' => $distributions->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'daily_breakdown' => $this->getDailyBreakdown($distributions),
        ];
    }

    private function calculateCompletionRate($distributions): float
    {
        $total = $distributions->count();
        if ($total === 0) return 0.0;

        $completed = $distributions->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 1);
    }

    private function calculateAverageCompletionTime($distributions): float
    {
        $completedDistributions = $distributions->where('status', 'completed')
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at');

        if ($completedDistributions->isEmpty()) {
            return 0.0;
        }

        $totalHours = $completedDistributions->sum(function ($distribution) {
            return $distribution->created_at->diffInHours($distribution->updated_at);
        });

        return round($totalHours / $completedDistributions->count(), 1);
    }

    private function getDailyDistributionCount($distributions): array
    {
        return $distributions->groupBy(function ($distribution) {
            return $distribution->created_at->format('Y-m-d');
        })->map->count()->toArray();
    }

    private function getDistributionTypeBreakdown($distributions): array
    {
        return $distributions->with('type')
            ->groupBy('type.name')
            ->map->count()
            ->toArray();
    }

    private function getDepartmentFlow(int $departmentId, $distributions): array
    {
        $outgoing = $distributions->where('origin_department_id', $departmentId)
            ->groupBy('destination_department_id')
            ->map->count();

        $incoming = $distributions->where('destination_department_id', $departmentId)
            ->groupBy('origin_department_id')
            ->map->count();

        return [
            'outgoing' => $outgoing->toArray(),
            'incoming' => $incoming->toArray(),
        ];
    }

    private function parsePeriodToDays(string $period): int
    {
        $periodMap = [
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
        ];

        return $periodMap[$period] ?? 30;
    }

    private function getDistributionPerformance(int $departmentId, Carbon $startDate): array
    {
        $distributions = Distribution::where(function ($q) use ($departmentId) {
            $q->where('origin_department_id', $departmentId)
                ->orWhere('destination_department_id', $departmentId);
        })->where('created_at', '>=', $startDate)->get();

        return [
            'total' => $distributions->count(),
            'completed' => $distributions->where('status', 'completed')->count(),
            'in_progress' => $distributions->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'avg_completion_time' => $this->calculateAverageCompletionTime($distributions),
        ];
    }

    private function getEfficiencyTrends(int $departmentId, int $days): array
    {
        // Get weekly efficiency data for the past period
        $weeks = ceil($days / 7);
        $weeklyData = [];

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            $weekDistributions = Distribution::where(function ($q) use ($departmentId) {
                $q->where('origin_department_id', $departmentId)
                    ->orWhere('destination_department_id', $departmentId);
            })->whereBetween('created_at', [$weekStart, $weekEnd])->get();

            $weeklyData[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'completion_rate' => $this->calculateCompletionRate($weekDistributions),
                'avg_completion_time' => $this->calculateAverageCompletionTime($weekDistributions),
                'total_distributions' => $weekDistributions->count(),
            ];
        }

        return array_reverse($weeklyData);
    }

    private function getBottleneckAnalysis(int $departmentId, Carbon $startDate): array
    {
        $distributions = Distribution::where(function ($q) use ($departmentId) {
            $q->where('origin_department_id', $departmentId)
                ->orWhere('destination_department_id', $departmentId);
        })->where('created_at', '>=', $startDate)
            ->whereNotIn('status', ['completed'])
            ->get();

        $statusCounts = $distributions->groupBy('status')->map->count();
        $stuckDistributions = $distributions->filter(function ($distribution) {
            return $distribution->created_at->diffInHours(Carbon::now()) > 48;
        });

        return [
            'stuck_distributions' => $stuckDistributions->count(),
            'status_breakdown' => $statusCounts->toArray(),
            'longest_pending' => $stuckDistributions->sortByDesc(function ($distribution) {
                return $distribution->created_at->diffInHours(Carbon::now());
            })->take(5)->values()->toArray(),
        ];
    }

    private function generateDepartmentStats($distributions): array
    {
        $stats = [];

        $departments = Department::all();
        foreach ($departments as $department) {
            $deptDistributions = $distributions->filter(function ($dist) use ($department) {
                return $dist->origin_department_id === $department->id ||
                    $dist->destination_department_id === $department->id;
            });

            $stats[$department->name] = [
                'total' => $deptDistributions->count(),
                'sent' => $deptDistributions->where('origin_department_id', $department->id)->count(),
                'received' => $deptDistributions->where('destination_department_id', $department->id)->count(),
                'completion_rate' => $this->calculateCompletionRate($deptDistributions),
            ];
        }

        return $stats;
    }

    private function calculateAverageVerificationTime($distributions): float
    {
        $verificationTimes = $distributions->filter(function ($dist) {
            return $dist->sender_verified_at && $dist->created_at;
        })->map(function ($dist) {
            return $dist->created_at->diffInHours($dist->sender_verified_at);
        });

        return $verificationTimes->isNotEmpty() ? round($verificationTimes->avg(), 1) : 0.0;
    }

    private function identifyWeeklyBottlenecks($distributions): array
    {
        $statusDurations = [];

        foreach ($distributions as $distribution) {
            if ($distribution->status !== 'completed') {
                $statusDurations[] = [
                    'distribution_id' => $distribution->id,
                    'status' => $distribution->status,
                    'hours_in_status' => $distribution->created_at->diffInHours(Carbon::now()),
                ];
            }
        }

        return collect($statusDurations)->groupBy('status')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'avg_hours' => round($group->avg('hours_in_status'), 1),
                    'max_hours' => $group->max('hours_in_status'),
                ];
            })->toArray();
    }

    private function getTopPerformers(Carbon $weekStart, Carbon $weekEnd): array
    {
        return UserActivity::whereBetween('created_at', [$weekStart, $weekEnd])
            ->where('activity_type', UserActivity::ACTIVITY_DISTRIBUTION_COMPLETE)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('count(*) as completions'))
            ->orderByDesc('completions')
            ->take(5)
            ->with('user:id,name')
            ->get()
            ->map(function ($activity) {
                return [
                    'user_id' => $activity->user_id,
                    'user_name' => $activity->user->name ?? 'Unknown',
                    'completions' => $activity->completions,
                ];
            })->toArray();
    }

    private function calculateUserHandlingTime(int $userId): float
    {
        $activities = UserActivity::forUser($userId)
            ->whereIn('activity_type', [
                UserActivity::ACTIVITY_DISTRIBUTION_VERIFY,
                UserActivity::ACTIVITY_DISTRIBUTION_COMPLETE,
            ])
            ->whereNotNull('duration_seconds')
            ->get();

        if ($activities->isEmpty()) {
            return 0.0;
        }

        $avgSeconds = $activities->avg('duration_seconds');
        return round($avgSeconds / 3600, 1); // Convert to hours
    }

    private function getDailyBreakdown($distributions): array
    {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->startOfWeek()->addDays($i);
            $dayDistributions = $distributions->filter(function ($dist) use ($date) {
                return $dist->created_at->isSameDay($date);
            });

            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'total' => $dayDistributions->count(),
                'completed' => $dayDistributions->where('status', 'completed')->count(),
            ];
        }

        return $days;
    }

    private function getSystemHealthMetrics(): array
    {
        $recentErrors = 0; // This would integrate with logging system
        $avgResponseTime = 0.5; // This would integrate with performance monitoring

        return [
            'status' => 'healthy',
            'recent_errors' => $recentErrors,
            'avg_response_time' => $avgResponseTime,
            'uptime_percentage' => 99.9,
        ];
    }
}
