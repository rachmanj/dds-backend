<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserPreferences;
use App\Services\UserPreferencesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserPreferencesServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserPreferencesService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserPreferencesService();
        $this->user = User::factory()->create();
    }

    public function test_get_user_preferences_creates_defaults_if_not_exists()
    {
        $preferences = $this->service->getUserPreferences($this->user->id);

        $this->assertInstanceOf(UserPreferences::class, $preferences);
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'light',
            'notification_settings' => 7,
            'email_notifications' => true,
            'push_notifications' => true,
            'language' => 'en',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function test_get_user_preferences_uses_cache()
    {
        // Create initial preferences
        $initialPreferences = $this->service->getUserPreferences($this->user->id);

        // Verify cache was set
        $cachedData = Cache::get("user_preferences_{$this->user->id}");
        $this->assertNotNull($cachedData);

        // Update preferences directly in database (bypassing service)
        UserPreferences::where('user_id', $this->user->id)->update(['theme' => 'dark']);

        // Get preferences again - should return cached version
        $cachedPreferences = $this->service->getUserPreferences($this->user->id);
        $this->assertSame('light', $cachedPreferences->theme); // Still cached 'light'
    }

    public function test_update_preferences_clears_cache()
    {
        // Create initial preferences to populate cache
        $this->service->getUserPreferences($this->user->id);

        // Update preferences
        $this->service->updatePreferences($this->user->id, ['theme' => 'dark']);

        // Verify cache was cleared
        $this->assertFalse(Cache::has("user_preferences_{$this->user->id}"));
    }

    public function test_set_theme_validates_input()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid theme. Must be one of: light, dark, system');

        $this->service->setTheme($this->user->id, 'invalid_theme');
    }

    public function test_set_theme_updates_preference()
    {
        $result = $this->service->setTheme($this->user->id, 'dark');

        $this->assertInstanceOf(UserPreferences::class, $result);
        $this->assertSame('dark', $result->theme);
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'dark'
        ]);
    }

    public function test_set_dashboard_layout()
    {
        $layout = [
            ['id' => 'widget1', 'x' => 0, 'y' => 0],
            ['id' => 'widget2', 'x' => 2, 'y' => 0]
        ];

        $result = $this->service->setDashboardLayout($this->user->id, $layout);

        $this->assertInstanceOf(UserPreferences::class, $result);
        $this->assertSame($layout, $result->dashboard_layout);
    }

    public function test_notification_management()
    {
        // Enable a specific notification
        $result = $this->service->enableNotification($this->user->id, 2); // 2^1
        $this->assertTrue($result->hasNotificationEnabled(2));

        // Disable a notification
        $result = $this->service->disableNotification($this->user->id, 1); // 2^0
        $this->assertFalse($result->hasNotificationEnabled(1));
    }

    public function test_set_language_validates_input()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language. Must be one of: en, id');

        $this->service->setLanguage($this->user->id, 'fr');
    }

    public function test_set_timezone_validates_input()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid timezone. Must be one of: Asia/Jakarta, UTC');

        $this->service->setTimezone($this->user->id, 'America/New_York');
    }

    public function test_reset_to_defaults()
    {
        // Create custom preferences
        UserPreferences::create([
            'user_id' => $this->user->id,
            'theme' => 'dark',
            'language' => 'id',
            'email_notifications' => false,
            'notification_settings' => 1
        ]);

        // Reset to defaults
        $result = $this->service->resetToDefaults($this->user->id);

        $this->assertInstanceOf(UserPreferences::class, $result);
        $this->assertSame('light', $result->theme);
        $this->assertSame('en', $result->language);
        $this->assertTrue($result->email_notifications);
        $this->assertSame(7, $result->notification_settings);
    }

    public function test_get_theme_helper()
    {
        $this->service->setTheme($this->user->id, 'dark');

        $theme = $this->service->getTheme($this->user->id);
        $this->assertSame('dark', $theme);
    }
}
