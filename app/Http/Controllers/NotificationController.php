<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string',
            'read' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $userId = Auth::id();
        $filters = $request->only(['type', 'read', 'per_page']);

        $notifications = $this->notificationService->getUserNotifications($userId, $filters);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem()
            ]
        ]);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $userId = Auth::id();

        $success = $this->notificationService->markAsRead($id, $userId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or access denied'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $userId = Auth::id();

        $count = $this->notificationService->markAllAsRead($userId);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'data' => ['count' => $count]
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $userId = Auth::id();

        $count = $this->notificationService->getUnreadCount($userId);

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count]
        ]);
    }

    public function getTypes(): JsonResponse
    {
        $types = $this->notificationService->getNotificationTypes();

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = auth()->id();

        $notification = \App\Models\Notification::forUser($userId)->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or access denied'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', Rule::in(['mark_read', 'delete'])],
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        $userId = auth()->id();
        $action = $request->input('action');
        $notificationIds = $request->input('notification_ids');

        $notifications = \App\Models\Notification::forUser($userId)
            ->whereIn('id', $notificationIds)
            ->get();

        if ($notifications->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid notifications found'
            ], 404);
        }

        $count = $notifications->count();

        switch ($action) {
            case 'mark_read':
                $notifications->each(function ($notification) {
                    $notification->markAsRead();
                });
                $message = "Marked {$count} notifications as read";
                break;

            case 'delete':
                $notifications->each(function ($notification) {
                    $notification->delete();
                });
                $message = "Deleted {$count} notifications";
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => ['count' => $count]
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:100',
            'message' => 'nullable|string|max:255',
            'data' => 'nullable|array'
        ]);

        $userId = auth()->id();

        $notification = $this->notificationService->sendRealTimeNotification(
            $userId,
            $request->input('type'),
            [
                'title' => $request->input('title'),
                'message' => $request->input('message'),
                'data' => $request->input('data', [])
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent',
            'data' => $notification->first()
        ]);
    }
}
