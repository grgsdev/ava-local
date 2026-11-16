<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @version V1
 */
class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Usando nossa permissão para garantir que só quem pode, pode criar.
        return $this->user()->can('create permissions');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // O nome da permissão é obrigatório, deve ser único na tabela 'permissions'
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'roles' => ['sometimes', 'array'], // <-- Adicionar
            'roles.*' => ['integer', 'exists:roles,id'], // <-- Adicionar

        ];
    }

    /**
     * Define a documentação dos parâmetros para o Scribe.
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'O nome da permissão (ex: edit posts).',
                'example' => 'create articles',
            ],
        ];
    }
}
