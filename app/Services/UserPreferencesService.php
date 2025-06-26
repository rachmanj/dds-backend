<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreferences;
use Illuminate\Support\Facades\Cache;

class UserPreferencesService
{
    public function getUserPreferences(int $userId): UserPreferences
    {
        // Use cache to improve performance
        return Cache::remember("user_preferences_{$userId}", 3600, function () use ($userId) {
            return UserPreferences::firstOrCreate(
                ['user_id' => $userId],
                [
                    'theme' => 'light',
                    'notification_settings' => 7, // All notifications enabled by default
                    'email_notifications' => true,
                    'push_notifications' => true,
                    'language' => 'en',
                    'timezone' => 'Asia/Jakarta',
                ]
            );
        });
    }

    public function updatePreferences(int $userId, array $preferences): UserPreferences
    {
        $userPreferences = UserPreferences::firstOrCreate(['user_id' => $userId]);

        $userPreferences->update($preferences);

        // Clear cache
        Cache::forget("user_preferences_{$userId}");

        return $userPreferences->fresh();
    }

    public function setTheme(int $userId, string $theme): UserPreferences
    {
        $validThemes = ['light', 'dark', 'system'];

        if (!in_array($theme, $validThemes)) {
            throw new \InvalidArgumentException('Invalid theme. Must be one of: ' . implode(', ', $validThemes));
        }

        return $this->updatePreferences($userId, ['theme' => $theme]);
    }

    public function setDashboardLayout(int $userId, array $layout): UserPreferences
    {
        return $this->updatePreferences($userId, ['dashboard_layout' => $layout]);
    }

    public function setNotificationSettings(int $userId, int $settings): UserPreferences
    {
        return $this->updatePreferences($userId, ['notification_settings' => $settings]);
    }

    public function enableNotification(int $userId, int $notificationType): UserPreferences
    {
        $preferences = $this->getUserPreferences($userId);
        $preferences->enableNotification($notificationType);
        $preferences->save();

        Cache::forget("user_preferences_{$userId}");

        return $preferences;
    }

    public function disableNotification(int $userId, int $notificationType): UserPreferences
    {
        $preferences = $this->getUserPreferences($userId);
        $preferences->disableNotification($notificationType);
        $preferences->save();

        Cache::forget("user_preferences_{$userId}");

        return $preferences;
    }

    public function setLanguage(int $userId, string $language): UserPreferences
    {
        $validLanguages = ['en', 'id'];

        if (!in_array($language, $validLanguages)) {
            throw new \InvalidArgumentException('Invalid language. Must be one of: ' . implode(', ', $validLanguages));
        }

        return $this->updatePreferences($userId, ['language' => $language]);
    }

    public function setTimezone(int $userId, string $timezone): UserPreferences
    {
        $validTimezones = ['Asia/Jakarta', 'UTC'];

        if (!in_array($timezone, $validTimezones)) {
            throw new \InvalidArgumentException('Invalid timezone. Must be one of: ' . implode(', ', $validTimezones));
        }

        return $this->updatePreferences($userId, ['timezone' => $timezone]);
    }

    public function getTheme(int $userId): string
    {
        return $this->getUserPreferences($userId)->theme;
    }

    public function resetToDefaults(int $userId): UserPreferences
    {
        $preferences = UserPreferences::where('user_id', $userId)->first();

        if ($preferences) {
            $preferences->update([
                'theme' => 'light',
                'dashboard_layout' => null,
                'notification_settings' => 7,
                'email_notifications' => true,
                'push_notifications' => true,
                'language' => 'en',
                'timezone' => 'Asia/Jakarta',
            ]);
        } else {
            $preferences = UserPreferences::create([
                'user_id' => $userId,
                'theme' => 'light',
                'notification_settings' => 7,
                'email_notifications' => true,
                'push_notifications' => true,
                'language' => 'en',
                'timezone' => 'Asia/Jakarta',
            ]);
        }

        Cache::forget("user_preferences_{$userId}");

        return $preferences;
    }
}
