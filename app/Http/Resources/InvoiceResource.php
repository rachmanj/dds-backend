<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'receive_date' => $this->receive_date,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'po_no' => $this->po_no,
            'receive_project' => $this->receive_project,
            'invoice_project' => $this->invoice_project,
            'payment_project' => $this->payment_project,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'type' => new InvoiceTypeResource($this->whenLoaded('type')),
            'payment_date' => $this->payment_date,
            'remarks' => $this->remarks,
            'cur_loc' => $this->cur_loc,
            'status' => $this->status,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'duration1' => $this->duration1,
            'duration2' => $this->duration2,
            'sap_doc' => $this->sap_doc,
            'flag' => $this->flag,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 