<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdditionalDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => new AdditionalDocumentTypeResource($this->whenLoaded('type')),
            'document_number' => $this->document_number,
            'document_date' => $this->document_date,
            'po_no' => $this->po_no,
            'project' => $this->project,
            'receive_date' => $this->receive_date,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'attachment' => $this->attachment,
            'remarks' => $this->remarks,
            'flag' => $this->flag,
            'status' => $this->status,
            'cur_loc' => $this->cur_loc,
            'ito_creator' => $this->ito_creator,
            'grpo_no' => $this->grpo_no,
            'origin_wh' => $this->origin_wh,
            'destination_wh' => $this->destination_wh,
            'batch_no' => $this->batch_no,
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
