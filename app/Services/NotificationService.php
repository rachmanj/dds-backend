<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\RealtimeSession;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendRealTimeNotification($userIds, string $type, array $data): Collection
    {
        // Ensure userIds is an array
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        $notifications = collect();

        foreach ($userIds as $userId) {
            $notification = Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $data['title'] ?? 'New Notification',
                'message' => $data['message'] ?? null,
                'data' => $data['data'] ?? null
            ]);

            $notifications->push($notification);

            // Broadcast to user via WebSocket
            $this->broadcastToUser($userId, 'notification.new', [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data,
                'created_at' => $notification->created_at->toISOString()
            ]);
        }

        return $notifications;
    }

    public function broadcastToUser(int $userId, string $event, array $data): void
    {
        // Get active sessions for user
        $sessions = RealtimeSession::forUser($userId)->active()->get();

        foreach ($sessions as $session) {
            // This would integrate with actual WebSocket server
            // For now, we'll store the broadcast intent
            Log::info("Broadcasting to user {$userId} on socket {$session->socket_id}", [
                'event' => $event,
                'data' => $data
            ]);
        }
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::forUser($userId)->unread()->count();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::forUser($userId)->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();

        // Broadcast read status update
        $this->broadcastToUser($userId, 'notification.read', [
            'id' => $notificationId
        ]);

        return true;
    }

    public function markAllAsRead(int $userId): int
    {
        $count = Notification::forUser($userId)->unread()->count();

        Notification::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);

        // Broadcast all read status update
        $this->broadcastToUser($userId, 'notification.all_read', [
            'count' => $count
        ]);

        return $count;
    }

    public function getUserNotifications(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::forUser($userId)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['read']) && $filters['read'] !== null) {
            if ($filters['read']) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    public function getNotificationTypes(): array
    {
        return [
            'distribution_created' => 'Distribution Created',
            'distribution_verified_sender' => 'Distribution Verified by Sender',
            'distribution_sent' => 'Distribution Sent',
            'distribution_received' => 'Distribution Received',
            'distribution_verified_receiver' => 'Distribution Verified by Receiver',
            'distribution_completed' => 'Distribution Completed',
            'distribution_discrepancy' => 'Distribution Discrepancy',
            'document_attachment' => 'Document Attached',
            'system_message' => 'System Message'
        ];
    }

    public function createDistributionNotification(string $type, $distribution, ?string $message = null): Collection
    {
        $title = $this->getDistributionNotificationTitle($type);
        $distributionNumber = $distribution->distribution_number ?? 'N/A';

        $data = [
            'title' => $title,
            'message' => $message ?? "Distribution #{$distributionNumber}",
            'data' => [
                'distribution_id' => $distribution->id,
                'distribution_number' => $distributionNumber,
                'type' => $type
            ]
        ];

        // Determine who should receive the notification
        $userIds = $this->getDistributionNotificationRecipients($type, $distribution);

        return $this->sendRealTimeNotification($userIds, $type, $data);
    }

    private function getDistributionNotificationTitle(string $type): string
    {
        return match ($type) {
            'distribution_created' => 'New Distribution Created',
            'distribution_verified_sender' => 'Distribution Verified by Sender',
            'distribution_sent' => 'Distribution Sent to You',
            'distribution_received' => 'Distribution Received',
            'distribution_verified_receiver' => 'Distribution Verified by Receiver',
            'distribution_completed' => 'Distribution Completed',
            'distribution_discrepancy' => 'Distribution Discrepancy Found',
            default => 'Distribution Update'
        };
    }

    private function getDistributionNotificationRecipients(string $type, $distribution): array
    {
        $recipients = [];

        switch ($type) {
            case 'distribution_sent':
                // Notify destination department users
                if ($distribution->destinationDepartment) {
                    $recipients = $distribution->destinationDepartment->users->pluck('id')->toArray();
                }
                break;

            case 'distribution_received':
            case 'distribution_verified_receiver':
            case 'distribution_completed':
                // Notify origin department users
                if ($distribution->originDepartment) {
                    $recipients = $distribution->originDepartment->users->pluck('id')->toArray();
                }
                break;

            case 'distribution_discrepancy':
                // Notify both departments
                $originUsers = $distribution->originDepartment?->users->pluck('id')->toArray() ?? [];
                $destUsers = $distribution->destinationDepartment?->users->pluck('id')->toArray() ?? [];
                $recipients = array_merge($originUsers, $destUsers);
                break;

            default:
                // For other types, notify the creator
                if ($distribution->creator) {
                    $recipients = [$distribution->creator->id];
                }
                break;
        }

        return array_unique($recipients);
    }
}
