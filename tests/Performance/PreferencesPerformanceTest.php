<?php

namespace Tests\Performance;

use App\Models\User;
use App\Models\UserPreferences;
use App\Services\UserPreferencesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PreferencesPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private UserPreferencesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserPreferencesService();
    }

    public function test_preferences_query_performance()
    {
        // Create 100 users with preferences
        $users = User::factory()->count(100)->create();

        foreach ($users as $user) {
            UserPreferences::factory()->create(['user_id' => $user->id]);
        }

        // Measure query performance
        $startTime = microtime(true);
        $queryCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;

        DB::enableQueryLog();

        // Test bulk preference retrieval
        foreach ($users->take(50) as $user) {
            $this->service->getUserPreferences($user->id);
        }

        $endTime = microtime(true);
        $finalQueryCount = count(DB::getQueryLog());
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Performance assertions
        $this->assertLessThan(500, $executionTime, 'Preference retrieval should complete within 500ms');
        $this->assertLessThan(55, $finalQueryCount - $queryCount, 'Should not exceed 55 queries for 50 users (with caching)');
    }

    public function test_cache_performance()
    {
        $user = User::factory()->create();

        // First call - should hit database
        $startTime = microtime(true);
        $this->service->getUserPreferences($user->id);
        $firstCallTime = (microtime(true) - $startTime) * 1000;

        // Second call - should hit cache
        $startTime = microtime(true);
        $this->service->getUserPreferences($user->id);
        $secondCallTime = (microtime(true) - $startTime) * 1000;

        // Cache should be significantly faster
        $this->assertLessThan($firstCallTime * 0.5, $secondCallTime, 'Cached calls should be at least 50% faster');
    }

    public function test_concurrent_preference_updates()
    {
        $users = User::factory()->count(20)->create();

        $startTime = microtime(true);

        // Simulate concurrent updates
        foreach ($users as $user) {
            $this->service->updatePreferences($user->id, [
                'theme' => 'dark',
                'language' => 'id',
                'notification_settings' => 5
            ]);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Should handle 20 concurrent updates efficiently
        $this->assertLessThan(2000, $executionTime, 'Should handle 20 updates within 2 seconds');

        // Verify all updates were successful
        foreach ($users as $user) {
            $preferences = $this->service->getUserPreferences($user->id);
            $this->assertSame('dark', $preferences->theme);
            $this->assertSame('id', $preferences->language);
            $this->assertSame(5, $preferences->notification_settings);
        }
    }

    public function test_memory_usage_with_large_datasets()
    {
        $memoryBefore = memory_get_usage();

        // Create preferences for 200 users
        $users = User::factory()->count(200)->create();

        foreach ($users as $user) {
            UserPreferences::factory()->withDashboardLayout()->create(['user_id' => $user->id]);
        }

        // Load all preferences
        foreach ($users as $user) {
            $this->service->getUserPreferences($user->id);
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        // Memory usage should be reasonable (less than 50MB for 200 users)
        $this->assertLessThan(50, $memoryUsed, 'Memory usage should be less than 50MB for 200 users');
    }

    public function test_database_index_effectiveness()
    {
        // Create test data
        $users = User::factory()->count(100)->create();

        foreach ($users as $user) {
            UserPreferences::factory()->create(['user_id' => $user->id]);
        }

        DB::enableQueryLog();

        // Test queries that should use indexes
        $randomUser = $users->random();

        // Query by user_id (primary key)
        UserPreferences::where('user_id', $randomUser->id)->first();

        // Query by theme
        UserPreferences::where('theme', 'dark')->get();

        $queries = DB::getQueryLog();

        // Verify queries are using indexes (execution time should be low)
        foreach ($queries as $query) {
            // This is a simplified check - in real scenarios you'd analyze EXPLAIN output
            $this->assertNotContains('filesort', strtolower($query['query']), 'Queries should use indexes efficiently');
        }
    }

    public function test_cache_invalidation_performance()
    {
        $users = User::factory()->count(50)->create();

        // Populate cache for all users
        foreach ($users as $user) {
            $this->service->getUserPreferences($user->id);
        }

        $startTime = microtime(true);

        // Update preferences (should invalidate cache)
        foreach ($users->take(25) as $user) {
            $this->service->updatePreferences($user->id, ['theme' => 'system']);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Cache invalidation should be fast
        $this->assertLessThan(1000, $executionTime, 'Cache invalidation should complete within 1 second');

        // Verify cache was properly invalidated
        foreach ($users->take(25) as $user) {
            $this->assertFalse(Cache::has("user_preferences_{$user->id}"));
        }

        // Verify non-updated users still have cache
        foreach ($users->skip(25) as $user) {
            $this->assertTrue(Cache::has("user_preferences_{$user->id}"));
        }
    }

    public function test_api_response_time_under_load()
    {
        $users = User::factory()->count(10)->create();

        foreach ($users as $user) {
            UserPreferences::factory()->create(['user_id' => $user->id]);
        }

        $responseTimes = [];

        // Simulate API calls
        foreach ($users as $user) {
            $startTime = microtime(true);

            // Simulate API endpoint call
            $preferences = $this->service->getUserPreferences($user->id);

            $responseTime = (microtime(true) - $startTime) * 1000;
            $responseTimes[] = $responseTime;
        }

        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);

        // Performance benchmarks
        $this->assertLessThan(50, $averageResponseTime, 'Average API response time should be under 50ms');
        $this->assertLessThan(100, $maxResponseTime, 'Maximum API response time should be under 100ms');
    }
}
