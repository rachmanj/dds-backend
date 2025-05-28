<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Convert full class name to simple type
        $documentType = match ($this->document_type) {
            'App\\Models\\Invoice' => 'invoice',
            'App\\Models\\AdditionalDocument' => 'additional_document',
            default => strtolower(class_basename($this->document_type))
        };

        return [
            'id' => $this->id,
            'distribution_id' => $this->distribution_id,
            'document_type' => $documentType,
            'document_id' => $this->document_id,
            'sender_verified' => $this->sender_verified,
            'receiver_verified' => $this->receiver_verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'document' => $this->whenLoaded('document', function () {
                // Return the actual document data
                if ($this->document) {
                    return [
                        'id' => $this->document->id,
                        'invoice_number' => $this->document->invoice_number ?? null,
                        'document_number' => $this->document->document_number ?? null,
                        'invoice_date' => $this->document->invoice_date ?? null,
                        'document_date' => $this->document->document_date ?? null,
                        'amount' => $this->document->amount ?? null,
                        'currency' => $this->document->currency ?? null,
                        'remarks' => $this->document->remarks ?? null,
                        // Add other fields as needed
                    ];
                }
                return null;
            }),
        ];
    }
}
