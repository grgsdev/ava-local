<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\User;
use App\Services\DateService;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @version V1
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // A rota já estará protegida por 'auth:sanctum'
    }

    public function rules(): array
    {
        $eighteenYearsAgo = now()->subYears(18)->format('Y-m-d');

        // Pega o ID do usuário, se houver um. Se não, o valor é nulo.
        $userId = request()->user()?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('users', 'nickname')->ignore($userId)->whereNull('deleted_at')],
            'username' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)->whereNull('deleted_at')],
            'pin_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('users', 'phone_number')->ignore($userId)->whereNull('deleted_at')],
            'birth_date' => ['sometimes', 'date', 'before_or_equal:' . $eighteenYearsAgo],
            //            'birth_date' => ['sometimes', 'required', 'date'],
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
            'address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address.longitude' => ['nullable', 'numeric', 'between:-180,180'],

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

    public function bodyParameters(): array
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
            'address.latitude' => [
                'description' => 'Sigla do estado (UF) do endereço.',
                'example' => '-25.50431'
            ],
            'address.longitude' => [
                'description' => 'Sigla do estado (UF) do endereço.',
                'example' => '-49.24273'
            ],

        ];
    }
}
