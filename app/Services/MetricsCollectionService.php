<?php

namespace App\Services;

use App\Models\WeeklyAnalytics;
use App\Models\UserActivity;
use App\Models\Distribution;
use App\Models\User;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetricsCollectionService
{
    /**
     * Collect weekly analytics data
     */
    public function collectWeeklyAnalytics(Carbon $weekStart = null): WeeklyAnalytics
    {
        $weekStart = $weekStart ?: Carbon::now()->subWeek()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        Log::info('Collecting weekly analytics', ['week_start' => $weekStart->format('Y-m-d')]);

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
            'efficiency_score' => $this->calculateEfficiencyScore($distributions),
        ];

        // Create or update weekly analytics
        $analytics = WeeklyAnalytics::updateForWeek($weekStart, [
            'total_distributions' => $totalDistributions,
            'completed_distributions' => $completedCount,
            'avg_completion_hours' => $avgCompletionHours,
            'active_users' => $activeUsers,
            'department_stats' => $departmentStats,
            'performance_metrics' => $performanceMetrics,
        ]);

        Log::info('Weekly analytics collected successfully', [
            'analytics_id' => $analytics->id,
            'total_distributions' => $totalDistributions,
            'completion_rate' => $performanceMetrics['completion_rate']
        ]);

        return $analytics;
    }

    /**
     * Collect user activity
     */
    public function collectUserActivity(
        int $userId,
        string $activityType,
        ?string $entityType = null,
        ?int $entityId = null,
        ?int $durationSeconds = null,
        array $metadata = []
    ): UserActivity {
        return UserActivity::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'duration_seconds' => $durationSeconds,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Generate performance alerts
     */
    public function generatePerformanceAlerts(): array
    {
        $alerts = [];
        $currentWeek = Carbon::now()->startOfWeek();
        $previousWeek = $currentWeek->copy()->subWeek();

        // Get current and previous week analytics
        $currentAnalytics = WeeklyAnalytics::where('week_start', $currentWeek)->first();
        $previousAnalytics = WeeklyAnalytics::where('week_start', $previousWeek)->first();

        if ($currentAnalytics && $previousAnalytics) {
            // Check for significant completion rate drop
            if ($currentAnalytics->completion_rate < $previousAnalytics->completion_rate - 10) {
                $alerts[] = [
                    'type' => 'completion_rate_drop',
                    'severity' => 'warning',
                    'message' => "Completion rate dropped by " .
                        round($previousAnalytics->completion_rate - $currentAnalytics->completion_rate, 1) . "%",
                    'current_value' => $currentAnalytics->completion_rate,
                    'previous_value' => $previousAnalytics->completion_rate,
                ];
            }

            // Check for increased processing time
            if ($currentAnalytics->avg_completion_hours > $previousAnalytics->avg_completion_hours * 1.5) {
                $alerts[] = [
                    'type' => 'processing_time_increase',
                    'severity' => 'warning',
                    'message' => "Average processing time increased significantly",
                    'current_value' => $currentAnalytics->avg_completion_hours,
                    'previous_value' => $previousAnalytics->avg_completion_hours,
                ];
            }
        }

        // Check for stuck distributions
        $stuckDistributions = Distribution::whereIn('status', ['sent', 'received'])
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->count();

        if ($stuckDistributions > 0) {
            $alerts[] = [
                'type' => 'stuck_distributions',
                'severity' => 'high',
                'message' => "{$stuckDistributions} distributions stuck for more than 3 days",
                'count' => $stuckDistributions,
            ];
        }

        // Check for low user activity
        $todayActivity = UserActivity::whereDate('created_at', Carbon::today())->distinct('user_id')->count();
        $avgActivity = UserActivity::whereBetween('created_at', [
            Carbon::now()->subDays(7),
            Carbon::today()
        ])->distinct('user_id')->count() / 7;

        if ($todayActivity < $avgActivity * 0.5) {
            $alerts[] = [
                'type' => 'low_user_activity',
                'severity' => 'medium',
                'message' => "User activity is below average",
                'current_value' => $todayActivity,
                'average_value' => round($avgActivity),
            ];
        }

        return $alerts;
    }

    /**
     * Collect workflow metrics
     */
    public function collectWorkflowMetrics(string $period = '30d'): array
    {
        $days = $this->parsePeriodToDays($period);
        $startDate = Carbon::now()->subDays($days);

        $distributions = Distribution::where('created_at', '>=', $startDate)->get();

        // Status distribution
        $statusBreakdown = $distributions->groupBy('status')->map->count();

        // Stage durations
        $stageDurations = $this->calculateStageDurations($distributions);

        // Bottleneck analysis
        $bottlenecks = $this->identifyBottlenecks($distributions);

        // Department workflow
        $departmentWorkflow = $this->analyzeDepartmentWorkflow($distributions);

        return [
            'status_breakdown' => $statusBreakdown,
            'stage_durations' => $stageDurations,
            'bottlenecks' => $bottlenecks,
            'department_workflow' => $departmentWorkflow,
            'total_distributions' => $distributions->count(),
            'period' => $period,
        ];
    }

    /**
     * Track usage patterns
     */
    public function trackUsagePatterns(string $period = '30d'): array
    {
        $days = $this->parsePeriodToDays($period);
        $startDate = Carbon::now()->subDays($days);

        // Peak hours analysis
        $peakHours = UserActivity::where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as activity_count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('activity_count', 'hour')
            ->toArray();

        // Department activity
        $departmentActivity = UserActivity::join('users', 'user_activities.user_id', '=', 'users.id')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->where('user_activities.created_at', '>=', $startDate)
            ->selectRaw('departments.name, COUNT(*) as activity_count')
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->pluck('activity_count', 'name')
            ->toArray();

        // User engagement patterns
        $userEngagement = $this->analyzeUserEngagement($startDate);

        // Feature usage
        $featureUsage = UserActivity::where('created_at', '>=', $startDate)
            ->selectRaw('activity_type, COUNT(*) as usage_count')
            ->groupBy('activity_type')
            ->get()
            ->pluck('usage_count', 'activity_type')
            ->toArray();

        return [
            'peak_hours' => $peakHours,
            'department_activity' => $departmentActivity,
            'user_engagement' => $userEngagement,
            'feature_usage' => $featureUsage,
            'period' => $period,
        ];
    }

    /**
     * Cleanup old data
     */
    public function cleanupOldData(int $retentionDays = 365): array
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        $cleaned = [];

        // Cleanup old user activities (keep recent for analytics)
        $oldActivities = UserActivity::where('created_at', '<', $cutoffDate)->count();
        UserActivity::where('created_at', '<', $cutoffDate)->delete();
        $cleaned['user_activities'] = $oldActivities;

        // Cleanup old weekly analytics (keep at least 2 years)
        $analyticsRetention = Carbon::now()->subYears(2)->startOfWeek();
        $oldAnalytics = WeeklyAnalytics::where('week_start', '<', $analyticsRetention)->count();
        WeeklyAnalytics::where('week_start', '<', $analyticsRetention)->delete();
        $cleaned['weekly_analytics'] = $oldAnalytics;

        Log::info('Analytics data cleanup completed', $cleaned);

        return $cleaned;
    }

    /**
     * Private helper methods
     */

    private function calculateAverageCompletionTime($distributions): float
    {
        $completedDistributions = $distributions->filter(function ($dist) {
            return $dist->status === 'completed' &&
                $dist->created_at &&
                $dist->updated_at;
        });

        if ($completedDistributions->isEmpty()) {
            return 0.0;
        }

        $totalHours = $completedDistributions->sum(function ($dist) {
            $createdTime = Carbon::parse($dist->created_at);
            $completedTime = Carbon::parse($dist->updated_at);
            return $createdTime->diffInHours($completedTime);
        });

        return round($totalHours / $completedDistributions->count(), 1);
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

            $completed = $deptDistributions->where('status', 'completed');

            $stats[$department->name] = [
                'total' => $deptDistributions->count(),
                'completed' => $completed->count(),
                'completion_rate' => $deptDistributions->count() > 0
                    ? round(($completed->count() / $deptDistributions->count()) * 100, 1)
                    : 0,
                'avg_hours' => $this->calculateAverageCompletionTime($completed),
            ];
        }

        return $stats;
    }

    private function calculateAverageVerificationTime($distributions): float
    {
        $verificationsWithTime = $distributions->filter(function ($dist) {
            return $dist->sender_verified_at && $dist->receiver_verified_at;
        });

        if ($verificationsWithTime->isEmpty()) {
            return 0.0;
        }

        $totalHours = $verificationsWithTime->sum(function ($dist) {
            $senderTime = Carbon::parse($dist->sender_verified_at);
            $receiverTime = Carbon::parse($dist->receiver_verified_at);
            return $senderTime->diffInHours($receiverTime);
        });

        return round($totalHours / $verificationsWithTime->count(), 1);
    }

    private function identifyWeeklyBottlenecks($distributions): array
    {
        $bottlenecks = [];

        // Check for distributions stuck in specific stages
        $stuckInSent = $distributions->where('status', 'sent')
            ->where('sent_at', '<', Carbon::now()->subDays(2))->count();

        $stuckInReceived = $distributions->where('status', 'received')
            ->where('received_at', '<', Carbon::now()->subDays(1))->count();

        if ($stuckInSent > 0) {
            $bottlenecks[] = [
                'stage' => 'sent',
                'count' => $stuckInSent,
                'description' => 'Distributions stuck in sent status'
            ];
        }

        if ($stuckInReceived > 0) {
            $bottlenecks[] = [
                'stage' => 'received',
                'count' => $stuckInReceived,
                'description' => 'Distributions stuck in received status'
            ];
        }

        return $bottlenecks;
    }

    private function getTopPerformers(Carbon $weekStart, Carbon $weekEnd): array
    {
        return User::withCount([
            'createdDistributions as distributions_created' => function ($query) use ($weekStart, $weekEnd) {
                $query->whereBetween('created_at', [$weekStart, $weekEnd]);
            },
            'createdDistributions as distributions_completed' => function ($query) use ($weekStart, $weekEnd) {
                $query->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->where('status', 'completed');
            }
        ])
            ->having('distributions_created', '>', 0)
            ->orderByDesc('distributions_completed')
            ->limit(5)
            ->get(['id', 'name', 'email'])
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'distributions_created' => $user->distributions_created,
                    'distributions_completed' => $user->distributions_completed,
                    'completion_rate' => $user->distributions_created > 0
                        ? round(($user->distributions_completed / $user->distributions_created) * 100, 1)
                        : 0,
                ];
            })->toArray();
    }

    private function calculateEfficiencyScore($distributions): float
    {
        if ($distributions->isEmpty()) {
            return 0.0;
        }

        $totalDistributions = $distributions->count();
        $completedDistributions = $distributions->where('status', 'completed')->count();
        $completionRate = $totalDistributions > 0 ? ($completedDistributions / $totalDistributions) : 0;

        // Calculate average completion time for completed distributions
        $avgCompletionHours = $this->calculateAverageCompletionTime(
            $distributions->where('status', 'completed')
        );

        // Target completion time is 24 hours
        $timeEfficiency = $avgCompletionHours > 0 ? min(24 / $avgCompletionHours, 1.0) : 1.0;

        // Combine completion rate and time efficiency
        $efficiency = ($completionRate * 0.7) + ($timeEfficiency * 0.3);

        return round($efficiency * 100, 1);
    }

    private function parsePeriodToDays(string $period): int
    {
        return match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };
    }

    private function calculateStageDurations($distributions): array
    {
        $stageDurations = [];

        foreach ($distributions as $dist) {
            if ($dist->sent_at && $dist->sender_verified_at) {
                $verificationDuration = Carbon::parse($dist->sender_verified_at)
                    ->diffInHours(Carbon::parse($dist->sent_at));
                $stageDurations['verification'][] = $verificationDuration;
            }

            if ($dist->received_at && $dist->sent_at) {
                $transitDuration = Carbon::parse($dist->received_at)
                    ->diffInHours(Carbon::parse($dist->sent_at));
                $stageDurations['transit'][] = $transitDuration;
            }

            if ($dist->receiver_verified_at && $dist->received_at) {
                $processingDuration = Carbon::parse($dist->receiver_verified_at)
                    ->diffInHours(Carbon::parse($dist->received_at));
                $stageDurations['processing'][] = $processingDuration;
            }
        }

        // Calculate averages
        foreach ($stageDurations as $stage => $durations) {
            $stageDurations[$stage] = [
                'average' => count($durations) > 0 ? round(array_sum($durations) / count($durations), 1) : 0,
                'count' => count($durations),
            ];
        }

        return $stageDurations;
    }

    private function identifyBottlenecks($distributions): array
    {
        $bottlenecks = [];
        $now = Carbon::now();

        // Stage-based bottleneck analysis
        $stages = [
            'draft' => ['limit' => 24, 'description' => 'Distributions in draft for too long'],
            'verified_by_sender' => ['limit' => 48, 'description' => 'Verified but not sent'],
            'sent' => ['limit' => 72, 'description' => 'Sent but not received'],
            'received' => ['limit' => 24, 'description' => 'Received but not verified'],
        ];

        foreach ($stages as $status => $config) {
            $stuck = $distributions->filter(function ($dist) use ($status, $config, $now) {
                if ($dist->status !== $status) return false;

                $stageTime = match ($status) {
                    'draft' => $dist->created_at,
                    'verified_by_sender' => $dist->sender_verified_at,
                    'sent' => $dist->sent_at,
                    'received' => $dist->received_at,
                    default => $dist->created_at,
                };

                return $stageTime && Carbon::parse($stageTime)->addHours($config['limit'])->isPast();
            });

            if ($stuck->count() > 0) {
                $bottlenecks[] = [
                    'stage' => $status,
                    'count' => $stuck->count(),
                    'description' => $config['description'],
                    'severity' => $stuck->count() > 5 ? 'high' : 'medium',
                ];
            }
        }

        return $bottlenecks;
    }

    private function analyzeDepartmentWorkflow($distributions): array
    {
        $workflow = [];

        foreach ($distributions as $dist) {
            $origin = $dist->originDepartment->name ?? 'Unknown';
            $destination = $dist->destinationDepartment->name ?? 'Unknown';

            $key = "{$origin} → {$destination}";

            if (!isset($workflow[$key])) {
                $workflow[$key] = [
                    'count' => 0,
                    'completed' => 0,
                    'avg_hours' => 0,
                ];
            }

            $workflow[$key]['count']++;

            if ($dist->status === 'completed') {
                $workflow[$key]['completed']++;

                if ($dist->created_at && $dist->updated_at) {
                    $hours = Carbon::parse($dist->created_at)
                        ->diffInHours(Carbon::parse($dist->updated_at));
                    $workflow[$key]['avg_hours'] =
                        ($workflow[$key]['avg_hours'] * ($workflow[$key]['completed'] - 1) + $hours)
                        / $workflow[$key]['completed'];
                }
            }
        }

        // Calculate completion rates
        foreach ($workflow as $key => &$data) {
            $data['completion_rate'] = $data['count'] > 0
                ? round(($data['completed'] / $data['count']) * 100, 1)
                : 0;
            $data['avg_hours'] = round($data['avg_hours'], 1);
        }

        return $workflow;
    }

    private function analyzeUserEngagement(Carbon $startDate): array
    {
        $users = User::withCount([
            'activities as total_activities' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
        ])->get();

        $engagement = [
            'highly_active' => $users->where('total_activities', '>=', 50)->count(),
            'moderately_active' => $users->whereBetween('total_activities', [10, 49])->count(),
            'low_activity' => $users->whereBetween('total_activities', [1, 9])->count(),
            'inactive' => $users->where('total_activities', 0)->count(),
        ];

        return $engagement;
    }
}
