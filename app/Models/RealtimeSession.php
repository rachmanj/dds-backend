<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealtimeSession extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'socket_id',
        'connected_at',
        'last_ping'
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'last_ping' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query, $timeoutMinutes = 5)
    {
        return $query->where('last_ping', '>', now()->subMinutes($timeoutMinutes));
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function updatePing()
    {
        $this->update(['last_ping' => now()]);
    }

    public function isActive($timeoutMinutes = 5): bool
    {
        return $this->last_ping > now()->subMinutes($timeoutMinutes);
    }
}
