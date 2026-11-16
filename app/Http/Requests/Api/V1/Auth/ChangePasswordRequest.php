<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Garante que o usuário está logado
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                // Regra customizada para verificar se a senha atual está correta
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, $this->user()->password)) {
                        $fail('A senha atual está incorreta.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],

        ];
    }

    public function bodyParameters(): array
    {
        return [
            'current_password' => ['description' => 'Senha atual no mínimo 8 caracteres.', 'example' => 'password'],
            'password' => ['description' => 'Senha com no mínimo 8 caracteres.', 'example' => 'password123'],
            'password_confirmation' => ['description' => 'A confirmação da nova senha (deve ser idêntica ao campo "password").', 'example' => 'password123'],
        ];
    }
}
