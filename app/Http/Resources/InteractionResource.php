<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'timestamp' => $this->timestamp,
            'data' => $this->data, // A coluna 'data' já é convertida para array pelo $casts no modelo
        ];
    }
}
