<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionHistory extends Model
{
    public $timestamps = false; // Only using created_at

    protected $fillable = [
        'distribution_id',
        'action',
        'user_id',
        'notes',
        'metadata',
        'created_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    // Relationships
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper method to create history entry
    public static function createEntry(int $distributionId, string $action, int $userId, ?string $notes = null, ?array $metadata = null): self
    {
        return self::create([
            'distribution_id' => $distributionId,
            'action' => $action,
            'user_id' => $userId,
            'notes' => $notes,
            'metadata' => $metadata,
            'created_at' => now()
        ]);
    }
}
