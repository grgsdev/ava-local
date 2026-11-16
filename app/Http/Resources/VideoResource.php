<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            // Carrega a coleção de interações apenas se elas tiverem sido incluídas na consulta (com ->with('interactions'))
            'interactions' => InteractionResource::collection($this->whenLoaded('interactions')),
        ];
    }
}
