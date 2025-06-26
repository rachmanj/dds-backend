<?php

namespace App\Console\Commands;

use App\Services\MetricsCollectionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CollectAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:collect 
                            {--force : Force collection even if data already exists}
                            {--cleanup : Cleanup old data after collection}
                            {--week-start= : Specific week start date (Y-m-d format)}';

    /**
     * The console command description.
     */
    protected $description = 'Collect weekly analytics data for dashboard metrics';

    private MetricsCollectionService $metricsService;

    /**
     * Create a new command instance.
     */
    public function __construct(MetricsCollectionService $metricsService)
    {
        parent::__construct();
        $this->metricsService = $metricsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting analytics collection...');

        try {
            // Determine week start date
            $weekStart = $this->option('week-start')
                ? Carbon::parse($this->option('week-start'))->startOfWeek()
                : Carbon::now()->subWeek()->startOfWeek();

            $this->info("Collecting analytics for week starting: {$weekStart->format('Y-m-d')}");

            // Collect weekly analytics
            $analytics = $this->metricsService->collectWeeklyAnalytics($weekStart);

            $this->info('✓ Weekly analytics collected successfully');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Week Start', $analytics->week_start->format('Y-m-d')],
                    ['Total Distributions', $analytics->total_distributions],
                    ['Completed Distributions', $analytics->completed_distributions],
                    ['Completion Rate', $analytics->completion_rate . '%'],
                    ['Avg Completion Hours', $analytics->avg_completion_hours],
                    ['Active Users', $analytics->active_users],
                ]
            );

            // Generate performance alerts
            $this->info('Generating performance alerts...');
            $alerts = $this->metricsService->generatePerformanceAlerts();

            if (empty($alerts)) {
                $this->info('✓ No performance alerts generated');
            } else {
                $this->warn("⚠ {" . count($alerts) . "} performance alerts generated:");
                foreach ($alerts as $alert) {
                    $severity = match ($alert['severity']) {
                        'high' => '🔴',
                        'warning' => '🟡',
                        'medium' => '🟠',
                        default => '🔵'
                    };
                    $this->line("  {$severity} {$alert['message']}");
                }
            }

            // Cleanup old data if requested
            if ($this->option('cleanup')) {
                $this->info('Cleaning up old analytics data...');
                $cleaned = $this->metricsService->cleanupOldData();
                $this->info("✓ Cleaned up {$cleaned['user_activities']} old user activities");
                $this->info("✓ Cleaned up {$cleaned['weekly_analytics']} old weekly analytics");
            }

            $this->info('🎉 Analytics collection completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Analytics collection failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
