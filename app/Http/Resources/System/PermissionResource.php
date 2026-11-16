<?php

namespace App\Http\Resources\System;

use App\Traits\FormatsDates; // <-- 1. Importar o Trait
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    use FormatsDates; // <-- 2. Usar o Trait

    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'roles' => RoleResource::collection($this->whenLoaded('roles')), // <-- Adicionar
            'roles_count' => $this->whenCounted('roles'),
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
