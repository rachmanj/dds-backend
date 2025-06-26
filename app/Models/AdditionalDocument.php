<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Services\DocumentTrackingService;

class AdditionalDocument extends Model
{
    protected $fillable = [
        'type_id',
        'document_number',
        'document_date',
        'po_no',
        'project',
        'receive_date',
        'created_by',
        'attachment',
        'remarks',
        'flag',
        'status',
        'cur_loc',
        'ito_creator',
        'grpo_no',
        'origin_wh',
        'destination_wh',
        'batch_no'
    ];

    protected $casts = [
        'document_date' => 'date',
        'receive_date' => 'date',
        'batch_no' => 'integer'
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AdditionalDocumentType::class, 'type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class);
    }

    public function distributions(): MorphToMany
    {
        return $this->morphToMany(Distribution::class, 'document', 'distribution_documents')
            ->withPivot(['sender_verified', 'receiver_verified'])
            ->withTimestamps();
    }

    /**
     * Get location history for this additional document
     */
    public function locationHistory()
    {
        return $this->hasMany(DocumentLocation::class, 'document_id')
            ->where('document_type', 'additional_document')
            ->orderBy('moved_at', 'desc');
    }

    /**
     * Get tracking events for this additional document
     */
    public function trackingEvents()
    {
        return $this->morphMany(TrackingEvent::class, 'trackable');
    }

    /**
     * Get current location code
     */
    public function getCurrentLocationAttribute(): ?string
    {
        return DocumentLocation::getCurrentLocation('additional_document', $this->id) ?? $this->cur_loc;
    }

    /**
     * Get location timeline with both locations and events
     */
    public function getLocationTimeline()
    {
        return app(DocumentTrackingService::class)->getLocationTimeline('additional_document', $this->id);
    }
}
