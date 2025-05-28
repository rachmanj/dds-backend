<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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
}
