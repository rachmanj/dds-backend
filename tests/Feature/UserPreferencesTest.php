<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_user_can_get_default_preferences()
    {
        $response = $this->getJson('/api/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'user_id',
                    'theme',
                    'dashboard_layout',
                    'notification_settings',
                    'email_notifications',
                    'push_notifications',
                    'language',
                    'timezone',
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user_id' => $this->user->id,
                    'theme' => 'light',
                    'notification_settings' => 7,
                    'email_notifications' => true,
                    'push_notifications' => true,
                    'language' => 'en',
                    'timezone' => 'Asia/Jakarta',
                ]
            ]);
    }

    public function test_user_can_update_theme()
    {
        $response = $this->putJson('/api/preferences/theme', [
            'theme' => 'dark'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Theme updated successfully',
                'data' => [
                    'theme' => 'dark'
                ]
            ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'dark'
        ]);
    }

    public function test_theme_validation_rejects_invalid_values()
    {
        $response = $this->putJson('/api/preferences/theme', [
            'theme' => 'invalid_theme'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    public function test_user_can_update_multiple_preferences()
    {
        $preferences = [
            'theme' => 'dark',
            'language' => 'id',
            'timezone' => 'UTC',
            'email_notifications' => false,
            'notification_settings' => 5
        ];

        $response = $this->putJson('/api/preferences', $preferences);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Preferences updated successfully'
            ]);

        $this->assertDatabaseHas('user_preferences', array_merge(
            ['user_id' => $this->user->id],
            $preferences
        ));
    }

    public function test_user_can_reset_preferences_to_defaults()
    {
        // First, modify some preferences
        UserPreferences::create([
            'user_id' => $this->user->id,
            'theme' => 'dark',
            'language' => 'id',
            'email_notifications' => false,
            'notification_settings' => 1
        ]);

        $response = $this->postJson('/api/preferences/reset');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Preferences reset to defaults successfully'
            ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'light',
            'language' => 'en',
            'email_notifications' => true,
            'notification_settings' => 7
        ]);
    }

    public function test_unauthorized_user_cannot_access_preferences()
    {
        $this->user->tokens()->delete();

        $response = $this->getJson('/api/preferences');
        $response->assertStatus(401);
    }
}
