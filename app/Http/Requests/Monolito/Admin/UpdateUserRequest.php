<?php

namespace App\Http\Requests\Monolito\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; //$this->user()->can('update users'); // Supondo que teremos esta permissão
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'], // Valida que cada item é um ID de papel válido
        ];
    }
}
