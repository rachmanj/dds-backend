<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreferences extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'theme',
        'dashboard_layout',
        'notification_settings',
        'email_notifications',
        'push_notifications',
        'language',
        'timezone',
    ];

    protected $casts = [
        'dashboard_layout' => 'array',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'notification_settings' => 'integer',
        'updated_at' => 'datetime',
    ];

    // Relationship
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods for notification settings (bitmask)
    public function hasNotificationEnabled(int $type): bool
    {
        return ($this->notification_settings & $type) === $type;
    }

    public function enableNotification(int $type): void
    {
        $this->notification_settings |= $type;
    }

    public function disableNotification(int $type): void
    {
        $this->notification_settings &= ~$type;
    }

    // Notification type constants
    const NOTIFICATION_DISTRIBUTION_CREATED = 1;    // 2^0
    const NOTIFICATION_DISTRIBUTION_VERIFIED = 2;   // 2^1
    const NOTIFICATION_DISTRIBUTION_RECEIVED = 4;   // 2^2
}
