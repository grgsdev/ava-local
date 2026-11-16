<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // Obtém o perfil do utilizador na empresa atual
            'role' => $this->whenLoaded('companies', function () {
                $company = app('current_company');
                return $this->companies->find($company->id)?->pivot->role;
            }),
            'status' => $this->whenLoaded('companies', function () {
                $company = app('current_company');
                return $this->companies->find($company->id)?->pivot->status;
            }),
            'avatar_url' => $this->when($this->hasMedia('avatar'), fn() => $this->getFirstMediaUrl('avatar')),

        ];
    }
}
