<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DistributionDocument extends Model
{
    protected $table = 'distribution_documents';

    protected $fillable = [
        'distribution_id',
        'document_type',
        'document_id',
        'sender_verified',
        'receiver_verified',
        'sender_verification_status',
        'sender_verification_notes',
        'receiver_verification_status',
        'receiver_verification_notes'
    ];

    protected $casts = [
        'sender_verified' => 'boolean',
        'receiver_verified' => 'boolean'
    ];

    // Relationships
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    // Helper methods to get specific document types
    public function invoice()
    {
        return $this->document_type === 'App\\Models\\Invoice' ? $this->document : null;
    }

    public function additionalDocument()
    {
        return $this->document_type === 'App\\Models\\AdditionalDocument' ? $this->document : null;
    }

    // Helper methods for verification status
    public function isVerifiedBySender(): bool
    {
        return $this->sender_verified && $this->sender_verification_status === 'verified';
    }

    public function isVerifiedByReceiver(): bool
    {
        return $this->receiver_verified && $this->receiver_verification_status === 'verified';
    }

    public function hasSenderDiscrepancy(): bool
    {
        return $this->sender_verified && in_array($this->sender_verification_status, ['missing', 'damaged']);
    }

    public function hasReceiverDiscrepancy(): bool
    {
        return $this->receiver_verified && in_array($this->receiver_verification_status, ['missing', 'damaged']);
    }
}
