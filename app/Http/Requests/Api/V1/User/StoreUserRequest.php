<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Services\DateService;

/**
 * @version V1
 */
class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'nickname' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nickname')->whereNull('deleted_at')],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->whereNull('deleted_at')],
            'pin_code' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone_number')->whereNull('deleted_at')],
            'birth_date' => ['required', 'date'],
            'avatar' => ['nullable', 'image', 'max:20480']
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => 0,

        ]);

        if ($this->input('birth_date')) {
            $parsedBirthDate = DateService::parseDate($this->input('birth_date'));
            if ($parsedBirthDate) {
                $this->merge([
                    'birth_date' => $parsedBirthDate->setTimezone('UTC')->toISOString(),
                ]);
            } else {
                $this->merge([
                    'birth_date' => 'error date', // handle error case appropriately
                ]);
            }
        }
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'Nome completo do usuário.',
                'example' => 'João da Silva'
            ],
            'email' => [
                'description' => 'Endereço de e-mail único do usuário.',
                'example' => 'joao.silva@lupa.app'
            ],
            'nickname' => [
                'description' => 'Apelido ou nome social do usuário.',
                'example' => 'Jão'
            ],
            'username' => [
                'description' => 'Nome de usuário único para login (sem espaços ou caracteres especiais).',
                'example' => 'joao_silva90'
            ],
            'pin_code' => [
                'description' => 'PIN de 4 a 6 dígitos para login rápido (deve ser armazenado com hash).',
                'example' => '123456'
            ],
            'password' => [
                'description' => 'Senha criptografada do usuário.',
                'example' => 'senhaForte123!'
            ],
            'phone_number' => [
                'description' => 'Número de telefone do usuário.',
                'example' => '+5541999998888'
            ],
            'birth_date' => [
                'description' => 'Data de nascimento do usuário.',
                'example' => '1990-01-15'
            ],
            'avatar' => [
                'description' => 'Imagem de perfil do usuário.',
            ],

        ];
    }
}
