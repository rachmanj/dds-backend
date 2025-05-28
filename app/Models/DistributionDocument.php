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
        'receiver_verified'
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
}
