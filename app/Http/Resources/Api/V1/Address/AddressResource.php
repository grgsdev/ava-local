<?php

namespace App\Http\Resources\Api\V1\Address;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Http\Resources\Api\V1\ActiveMotive\ActiveMotiveResource;
use App\Http\Resources\Api\V1\ActivityLog\ActivityLogResource;

use App\Traits\FormatsDates;

/**
 * @version V1
 */
class AddressResource extends JsonResource
{
    use FormatsDates;
    public static $wrap = null;
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'addressable_id' => $this->addressable_id,
            'addressable_type' => $this->addressable_type,
            'type' => $this->type,
            'zip_code' => $this->zip_code,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'latitude' => $this->latitude,   // <-- Adicionar
            'longitude' => $this->longitude, // <-- Adicionar

            'is_active' => $this->is_active,
            'is_active_text' => $this->is_active_text,
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
            'deleted_at' => $this->formatDate($this->deleted_at),
            // 'active_motives' => ActiveMotiveResource::collection($this->whenLoaded('activeMotives')),
            // 'activities' => ActivityLogResource::collection($this->whenLoaded('activities'))
        ];
    }
}
