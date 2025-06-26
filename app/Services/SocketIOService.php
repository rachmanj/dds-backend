<?php

namespace App\Services;

use App\Models\RealtimeSession;
use Illuminate\Support\Facades\Log;

class SocketIOService
{
    protected $server;
    protected $port;

    public function __construct()
    {
        $this->port = config('app.socket_port', 3001);
    }

    public function startServer(): void
    {
        Log::info("Starting Socket.IO server on port {$this->port}");

        // This would be implemented with actual Socket.IO server
        // For now, we'll just log the intent
        Log::info("Socket.IO server would be started here");
    }

    public function handleUserJoin($socket, array $data): void
    {
        $userId = $data['user_id'] ?? null;
        $socketId = $socket['id'] ?? null;

        if (!$userId || !$socketId) {
            Log::warning('User join failed: missing user_id or socket_id', $data);
            return;
        }

        // Create or update session
        RealtimeSession::updateOrCreate(
            ['user_id' => $userId],
            [
                'id' => uniqid('session_'),
                'socket_id' => $socketId,
                'connected_at' => now(),
                'last_ping' => now()
            ]
        );

        // Join user to their personal channel
        $this->joinChannel($socket, "user.{$userId}");

        Log::info("User {$userId} joined with socket {$socketId}");

        // Notify about online status
        $this->broadcastToChannel("user.{$userId}", 'user.online', [
            'user_id' => $userId,
            'timestamp' => now()->toISOString()
        ]);
    }

    public function handleUserDisconnect($socket): void
    {
        $socketId = $socket['id'] ?? null;

        if (!$socketId) {
            return;
        }

        // Find and remove session
        $session = RealtimeSession::where('socket_id', $socketId)->first();

        if ($session) {
            $userId = $session->user_id;
            $session->delete();

            Log::info("User {$userId} disconnected from socket {$socketId}");

            // Notify about offline status
            $this->broadcastToChannel("user.{$userId}", 'user.offline', [
                'user_id' => $userId,
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    public function handlePing($socket, array $data): void
    {
        $socketId = $socket['id'] ?? null;

        if (!$socketId) {
            return;
        }

        $session = RealtimeSession::where('socket_id', $socketId)->first();

        if ($session) {
            $session->updatePing();
        }
    }

    public function broadcastToChannel(string $channel, string $event, array $data): void
    {
        // This would integrate with actual Socket.IO server
        Log::info("Broadcasting to channel {$channel}", [
            'event' => $event,
            'data' => $data
        ]);
    }

    public function broadcastToUser(int $userId, string $event, array $data): void
    {
        $this->broadcastToChannel("user.{$userId}", $event, $data);
    }

    public function joinChannel($socket, string $channel): void
    {
        // This would implement actual channel joining
        Log::info("Socket {$socket['id']} joining channel {$channel}");
    }

    public function leaveChannel($socket, string $channel): void
    {
        // This would implement actual channel leaving
        Log::info("Socket {$socket['id']} leaving channel {$channel}");
    }

    public function getActiveUsers(): array
    {
        return RealtimeSession::active()
            ->with('user:id,name,email')
            ->get()
            ->map(function ($session) {
                return [
                    'user_id' => $session->user_id,
                    'name' => $session->user->name,
                    'email' => $session->user->email,
                    'connected_at' => $session->connected_at->toISOString(),
                    'last_ping' => $session->last_ping->toISOString()
                ];
            })
            ->toArray();
    }

    public function getUserSessions(int $userId): array
    {
        return RealtimeSession::forUser($userId)
            ->active()
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'socket_id' => $session->socket_id,
                    'connected_at' => $session->connected_at->toISOString(),
                    'last_ping' => $session->last_ping->toISOString(),
                    'is_active' => $session->isActive()
                ];
            })
            ->toArray();
    }

    public function cleanupInactiveSessions(int $timeoutMinutes = 5): int
    {
        $count = RealtimeSession::where('last_ping', '<', now()->subMinutes($timeoutMinutes))
            ->count();

        RealtimeSession::where('last_ping', '<', now()->subMinutes($timeoutMinutes))
            ->delete();

        if ($count > 0) {
            Log::info("Cleaned up {$count} inactive sessions");
        }

        return $count;
    }
}
