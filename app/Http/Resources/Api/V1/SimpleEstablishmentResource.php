<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleEstablishmentResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'approved_status' => $this->approved_status,
            'structure_type' => $this->structure_type,
            'structure_type_text' => $this->structure_type_text,
            'is_ecommerce' => $this->is_ecommerce,
            'status_open' => $this->status_open,
            'status_open_details' => $this->status_open_details,
            'has_appointment' => $this->has_appointment,
            'is_24h' => $this->ais_24h,


        ];
    }
}
