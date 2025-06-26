<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreferences;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferencesFactory extends Factory
{
    protected $model = UserPreferences::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'theme' => $this->faker->randomElement(['light', 'dark', 'system']),
            'dashboard_layout' => null,
            'notification_settings' => $this->faker->numberBetween(0, 7),
            'email_notifications' => $this->faker->boolean(),
            'push_notifications' => $this->faker->boolean(),
            'language' => $this->faker->randomElement(['en', 'id']),
            'timezone' => $this->faker->randomElement(['Asia/Jakarta', 'UTC']),
        ];
    }

    public function withDarkTheme(): static
    {
        return $this->state(fn(array $attributes) => [
            'theme' => 'dark',
        ]);
    }

    public function withLightTheme(): static
    {
        return $this->state(fn(array $attributes) => [
            'theme' => 'light',
        ]);
    }

    public function withAllNotifications(): static
    {
        return $this->state(fn(array $attributes) => [
            'notification_settings' => 7, // All notifications enabled (111 in binary)
            'email_notifications' => true,
            'push_notifications' => true,
        ]);
    }

    public function withNoNotifications(): static
    {
        return $this->state(fn(array $attributes) => [
            'notification_settings' => 0, // No notifications enabled
            'email_notifications' => false,
            'push_notifications' => false,
        ]);
    }

    public function withIndonesianLocale(): static
    {
        return $this->state(fn(array $attributes) => [
            'language' => 'id',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function withDashboardLayout(): static
    {
        return $this->state(fn(array $attributes) => [
            'dashboard_layout' => [
                ['id' => 'widget1', 'x' => 0, 'y' => 0, 'w' => 2, 'h' => 2],
                ['id' => 'widget2', 'x' => 2, 'y' => 0, 'w' => 2, 'h' => 1],
                ['id' => 'widget3', 'x' => 0, 'y' => 2, 'w' => 4, 'h' => 1],
            ],
        ]);
    }
}
