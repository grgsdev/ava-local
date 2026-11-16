<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @version V1
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => ['description' => 'E-mail do usuário.', 'example' => 'ana@exemplo.com'],
            'password' => ['description' => 'Senha do usuário.', 'example' => 'password123'],
            'device_name' => ['description' => 'Nome do dispositivo para identificar o token (ex: "iPhone da Ana").', 'example' => 'Meu App React Native'],
        ];
    }
}
