<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Distribution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'distribution_number',
        'type_id',
        'origin_department_id',
        'destination_department_id',
        'document_type',
        'created_by',
        'sender_verified_at',
        'sender_verified_by',
        'sent_at',
        'received_at',
        'receiver_verified_at',
        'receiver_verified_by',
        'notes',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'sender_verified_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'receiver_verified_at' => 'datetime'
    ];

    // Relationships
    public function type(): BelongsTo
    {
        return $this->belongsTo(DistributionType::class, 'type_id');
    }

    public function originDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'origin_department_id');
    }

    public function destinationDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'destination_department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function senderVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_verified_by');
    }

    public function receiverVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_verified_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DistributionHistory::class)->orderBy('created_at', 'desc');
    }

    // Direct relationship to pivot table
    public function documents(): HasMany
    {
        return $this->hasMany(DistributionDocument::class);
    }

    // Polymorphic relationships for documents
    public function invoices(): MorphToMany
    {
        return $this->morphedByMany(Invoice::class, 'document', 'distribution_documents')
            ->withPivot(['sender_verified', 'receiver_verified'])
            ->withTimestamps();
    }

    public function additionalDocuments(): MorphToMany
    {
        return $this->morphedByMany(AdditionalDocument::class, 'document', 'distribution_documents')
            ->withPivot(['sender_verified', 'receiver_verified'])
            ->withTimestamps();
    }

    // Helper method to get all documents
    public function getAllDocuments()
    {
        $invoices = $this->invoices->map(function ($invoice) {
            $invoice->document_type = 'invoice';
            return $invoice;
        });

        $additionalDocs = $this->additionalDocuments->map(function ($doc) {
            $doc->document_type = 'additional_document';
            return $doc;
        });

        return $invoices->concat($additionalDocs);
    }

    // Status helper methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isVerifiedBySender(): bool
    {
        return $this->status === 'verified_by_sender';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isVerifiedByReceiver(): bool
    {
        return $this->status === 'verified_by_receiver';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // Document type helper methods
    public function isInvoiceDistribution(): bool
    {
        return $this->document_type === 'invoice';
    }

    public function isAdditionalDocumentDistribution(): bool
    {
        return $this->document_type === 'additional_document';
    }
}
