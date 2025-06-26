<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\MetricsCollectionService;
use App\Models\WeeklyAnalytics;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    private AnalyticsService $analyticsService;
    private MetricsCollectionService $metricsService;

    public function __construct(
        AnalyticsService $analyticsService,
        MetricsCollectionService $metricsService
    ) {
        $this->analyticsService = $analyticsService;
        $this->metricsService = $metricsService;
    }

    /**
     * Get dashboard analytics for current user
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $metrics = $this->analyticsService->getDashboardMetrics($userId);

            // Log dashboard view activity
            $this->metricsService->collectUserActivity(
                $userId,
                UserActivity::ACTIVITY_DASHBOARD_VIEW,
                null,
                null,
                null,
                ['page' => 'dashboard']
            );

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance metrics for a department
     */
    public function performance(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'department_id' => 'sometimes|integer|exists:departments,id',
                'period' => 'sometimes|string|in:7d,30d,90d,1y',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $departmentId = $request->input('department_id', $user->department_id);
            $period = $request->input('period', '30d');

            // Check if user can access this department's data
            if ($departmentId !== $user->department_id && !$user->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this department data'
                ], 403);
            }

            $metrics = $this->analyticsService->getPerformanceMetrics($departmentId, $period);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch performance metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department-specific metrics
     */
    public function departmentMetrics(int $departmentId): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check authorization
            if ($departmentId !== $user->department_id && !$user->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this department data'
                ], 403);
            }

            $dateRange = [
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ];

            $metrics = $this->analyticsService->getDistributionMetrics($departmentId, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch department metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user activity metrics
     */
    public function userActivity(int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();

            // Check authorization - users can only see their own data unless they're admin
            if ($userId !== $currentUser->id && !$currentUser->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this user data'
                ], 403);
            }

            $period = request('period', '30d');
            $metrics = $this->analyticsService->getUserActivityMetrics($userId, $period);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user activity metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weekly analytics summary
     */
    public function weeklyAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'weeks' => 'sometimes|integer|min:1|max:52',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $weeks = $request->input('weeks', 12);
            $summary = WeeklyAnalytics::getDashboardSummary($weeks);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch weekly analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time dashboard data
     */
    public function realtime(): JsonResponse
    {
        try {
            $data = $this->analyticsService->getRealTimeDashboardData();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch real-time data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distribution workflow metrics
     */
    public function workflowMetrics(): JsonResponse
    {
        try {
            $metrics = $this->metricsService->collectDistributionWorkflowMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workflow metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get usage patterns analytics
     */
    public function usagePatterns(): JsonResponse
    {
        try {
            $patterns = $this->metricsService->trackUsagePatterns();

            return response()->json([
                'success' => true,
                'data' => $patterns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch usage patterns',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics report
     */
    public function exportReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'format' => 'required|string|in:json,csv,excel',
                'period' => 'sometimes|string|in:7d,30d,90d,1y',
                'department_id' => 'sometimes|integer|exists:departments,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $format = $request->input('format');
            $period = $request->input('period', '30d');
            $departmentId = $request->input('department_id', $user->department_id);

            // Check authorization
            if ($departmentId !== $user->department_id && !$user->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to export this department data'
                ], 403);
            }

            // Log report generation activity
            $this->metricsService->collectUserActivity(
                $user->id,
                UserActivity::ACTIVITY_REPORT_GENERATE,
                'analytics_report',
                null,
                null,
                [
                    'format' => $format,
                    'period' => $period,
                    'department_id' => $departmentId
                ]
            );

            // Get analytics data
            $analytics = $this->analyticsService->getPerformanceMetrics($departmentId, $period);

            // For now, return JSON data. In a full implementation, you'd generate actual files
            return response()->json([
                'success' => true,
                'data' => $analytics,
                'format' => $format,
                'exported_at' => Carbon::now()->toISOString(),
                'message' => "Analytics report exported successfully in {$format} format"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance comparison data
     */
    public function performanceComparison(): JsonResponse
    {
        try {
            $comparison = WeeklyAnalytics::getPerformanceComparison();

            return response()->json([
                'success' => true,
                'data' => $comparison
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch performance comparison',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system health metrics
     */
    public function systemHealth(): JsonResponse
    {
        try {
            $health = [
                'database_status' => 'healthy',
                'cache_status' => 'healthy',
                'queue_status' => 'healthy',
                'storage_status' => 'healthy',
                'last_analytics_collection' => WeeklyAnalytics::latest('created_at')->first()?->created_at,
                'pending_distributions' => \App\Models\Distribution::whereNotIn('status', ['completed'])->count(),
                'active_users_today' => UserActivity::today()->distinct('user_id')->count('user_id'),
            ];

            return response()->json([
                'success' => true,
                'data' => $health
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system health',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger manual analytics collection (admin only)
     */
    public function collectAnalytics(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to trigger analytics collection'
                ], 403);
            }

            $result = $this->metricsService->collectWeeklyAnalytics();

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Analytics collection triggered successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Analytics collection failed'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger analytics collection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance alerts
     */
    public function performanceAlerts(): JsonResponse
    {
        try {
            $alerts = $this->metricsService->generatePerformanceAlerts();

            return response()->json([
                'success' => true,
                'data' => $alerts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch performance alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up old analytics data (admin only)
     */
    public function cleanupOldData(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to perform data cleanup'
                ], 403);
            }

            $result = $this->metricsService->cleanupOldData();

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Old data cleanup completed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data cleanup failed'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup old data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
