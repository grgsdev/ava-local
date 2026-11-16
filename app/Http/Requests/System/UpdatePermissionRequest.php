<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\System\Permission; // Importe o model que criamos

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Garante que o usuário pode editar permissões
        return $this->user()->can('update permissions');
    }

    public function rules(): array
    {
        // Pega o ID da permissão da rota para a regra 'unique'
        $permissionId = $this->route('permission')->id;

        return [
            // 'sometimes' significa que o campo só é validado se estiver presente na requisição
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:permissions,name,' . $permissionId],
            'roles' => ['sometimes', 'array'], // <-- Adicionar
            'roles.*' => ['integer', 'exists:roles,id'], // <-- Adicionar

        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'O nome da permissão (ex: edit posts).',
                'example' => 'update articles',
            ],
        ];
    }
}
