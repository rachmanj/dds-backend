<?php

namespace App\Http\Controllers;

use App\Services\UserPreferencesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserPreferencesController extends Controller
{
    public function __construct(
        private UserPreferencesService $preferencesService
    ) {}

    public function show(): JsonResponse
    {
        try {
            $preferences = $this->preferencesService->getUserPreferences(Auth::id());

            return response()->json([
                'status' => 'success',
                'data' => $preferences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch preferences'
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'theme' => 'sometimes|in:light,dark,system',
                'dashboard_layout' => 'sometimes|array',
                'notification_settings' => 'sometimes|integer|min:0|max:7',
                'email_notifications' => 'sometimes|boolean',
                'push_notifications' => 'sometimes|boolean',
                'language' => 'sometimes|in:en,id',
                'timezone' => 'sometimes|in:Asia/Jakarta,UTC',
            ]);

            $preferences = $this->preferencesService->updatePreferences(
                Auth::id(),
                $validated
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Preferences updated successfully',
                'data' => $preferences
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update preferences'
            ], 500);
        }
    }

    public function updateTheme(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'theme' => 'required|in:light,dark,system'
            ]);

            $preferences = $this->preferencesService->setTheme(
                Auth::id(),
                $validated['theme']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Theme updated successfully',
                'data' => [
                    'theme' => $preferences->theme
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update theme'
            ], 500);
        }
    }

    public function updateDashboardLayout(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'layout' => 'required|array'
            ]);

            $preferences = $this->preferencesService->setDashboardLayout(
                Auth::id(),
                $validated['layout']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard layout updated successfully',
                'data' => [
                    'dashboard_layout' => $preferences->dashboard_layout
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update dashboard layout'
            ], 500);
        }
    }

    public function updateNotificationSettings(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'settings' => 'required|integer|min:0|max:7'
            ]);

            $preferences = $this->preferencesService->setNotificationSettings(
                Auth::id(),
                $validated['settings']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Notification settings updated successfully',
                'data' => [
                    'notification_settings' => $preferences->notification_settings
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update notification settings'
            ], 500);
        }
    }

    public function resetToDefaults(): JsonResponse
    {
        try {
            $preferences = $this->preferencesService->resetToDefaults(Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Preferences reset to defaults successfully',
                'data' => $preferences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset preferences'
            ], 500);
        }
    }
}
