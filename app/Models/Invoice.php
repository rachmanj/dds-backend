<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Services\DocumentTrackingService;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'faktur_no',
        'invoice_date',
        'receive_date',
        'supplier_id',
        'po_no',
        'receive_project',
        'invoice_project',
        'payment_project',
        'currency',
        'amount',
        'type_id',
        'payment_date',
        'remarks',
        'cur_loc',
        'status',
        'created_by',
        'duration1',
        'duration2',
        'sap_doc',
        'flag'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'receive_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(InvoiceType::class, 'type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function additionalDocuments(): BelongsToMany
    {
        return $this->belongsToMany(AdditionalDocument::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InvoiceAttachment::class);
    }

    public function distributions(): MorphToMany
    {
        return $this->morphToMany(Distribution::class, 'document', 'distribution_documents')
            ->withPivot(['sender_verified', 'receiver_verified'])
            ->withTimestamps();
    }

    /**
     * Get location history for this invoice
     */
    public function locationHistory()
    {
        return $this->hasMany(DocumentLocation::class, 'document_id')
            ->where('document_type', 'invoice')
            ->orderBy('moved_at', 'desc');
    }

    /**
     * Get tracking events for this invoice
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
        return DocumentLocation::getCurrentLocation('invoice', $this->id) ?? $this->cur_loc;
    }

    /**
     * Get location timeline with both locations and events
     */
    public function getLocationTimeline()
    {
        return app(DocumentTrackingService::class)->getLocationTimeline('invoice', $this->id);
    }
}
