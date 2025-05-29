<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'distribution_number' => $this->distribution_number,
            'type_id' => $this->type_id,
            'origin_department_id' => $this->origin_department_id,
            'destination_department_id' => $this->destination_department_id,
            'document_type' => $this->document_type,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'sender_verified_by' => $this->sender_verified_by,
            'receiver_verified_by' => $this->receiver_verified_by,
            'sender_verified_at' => $this->sender_verified_at,
            'receiver_verified_at' => $this->receiver_verified_at,
            'sender_verification_notes' => $this->sender_verification_notes,
            'receiver_verification_notes' => $this->receiver_verification_notes,
            'has_discrepancies' => $this->has_discrepancies,
            'sent_at' => $this->sent_at,
            'received_at' => $this->received_at,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at,

            // Relationships
            'type' => $this->whenLoaded('type'),
            'origin_department' => $this->whenLoaded('originDepartment'),
            'destination_department' => $this->whenLoaded('destinationDepartment'),
            'creator' => $this->whenLoaded('creator'),
            'sender_verifier' => $this->whenLoaded('senderVerifier'),
            'receiver_verifier' => $this->whenLoaded('receiverVerifier'),
            'documents' => DistributionDocumentResource::collection($this->whenLoaded('documents')),
            'invoices' => $this->whenLoaded('invoices'),
            'additional_documents' => $this->whenLoaded('additionalDocuments'),
            'histories' => $this->whenLoaded('histories'),
        ];
    }
}
