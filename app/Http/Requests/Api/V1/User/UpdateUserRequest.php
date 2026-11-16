<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Services\DateService;

/**
 * @version V1
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user_id)->whereNull('deleted_at')],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('users', 'nickname')->ignore($this->user_id)->whereNull('deleted_at')],
            'username' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($this->user_id)->whereNull('deleted_at')],
            'pin_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('users', 'phone_number')->ignore($this->user_id)->whereNull('deleted_at')],
            'birth_date' => ['sometimes', 'required', 'date'],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:20480'],
            'address' => ['sometimes', 'nullable', 'array'],
            'address.type' => ['required_with:address', 'string', 'max:50'],
            'address.zip_code' => ['required_with:address', 'string', 'max:10'],
            'address.street' => ['required_with:address', 'string', 'max:255'],
            'address.number' => ['required_with:address', 'string', 'max:20'],
            'address.neighborhood' => ['required_with:address', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.state' => ['required_with:address', 'string', 'max:2'],
            'address.complement' => ['nullable', 'string', 'max:255'],

        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([]);

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

            'address' => [
                'description' => 'Objeto contendo os dados do endereço do estabelecimento (opcional).',
            ],
            'address.zip_code' => [
                'description' => 'CEP do endereço.',
                'example' => '80000-000'
            ],
            'address.street' => [
                'description' => 'Rua do endereço.',
                'example' => 'Rua das Flores'
            ],
            'address.number' => [
                'description' => 'Número do endereço.',
                'example' => '123'
            ],
            'address.complement' => [
                'description' => 'Complemento do endereço (opcional).',
                'example' => 'Sala 101'
            ],
            'address.neighborhood' => [
                'description' => 'Bairro do endereço.',
                'example' => 'Centro'
            ],
            'address.city' => [
                'description' => 'Cidade do endereço.',
                'example' => 'Curitiba'
            ],
            'address.state' => [
                'description' => 'Sigla do estado (UF) do endereço.',
                'example' => 'PR'
            ],

        ];
    }
}
