<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Services\DateService;
use Illuminate\Foundation\Http\FormRequest;

class AffiliateRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Endpoint público
    }

    public function rules(): array
    {
        // Calcula a data máxima permitida para o usuário ter 18 anos
        $eighteenYearsAgo = now()->subYears(18)->format('Y-m-d');

        return [
            // Regras para o Usuário (User)
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],

            'birth_date' => ['required', 'date', 'before_or_equal:' . $eighteenYearsAgo],
            'avatar' => ['nullable', 'image', 'max:20048'],
            'phone_number' => ['required', 'string'],
            // Regras para o Estabelecimento (Establishment)
            'establishment_name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:establishment_categories,id'],
            'site' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'is_ecommerce' => ['nullable', 'boolean'],
            'has_appointment' => ['nullable', 'boolean'],
            'is_24h' => ['nullable', 'boolean'],

            // Regras para o Endereço
            'address' => ['nullable', 'array'],
            'address.zip_code' => ['required_with:address', 'string', 'max:10'],
            'address.street' => ['required_with:address', 'string', 'max:255'],
            'address.number' => ['required_with:address', 'string', 'max:20'],
            'address.neighborhood' => ['required_with:address', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.state' => ['required_with:address', 'string', 'max:2'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address.longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'auto_approve_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => 1,
            'is_ecommerce' => $this->boolean('is_ecommerce'),
            'has_appointment' => $this->boolean('has_appointment'),
            'is_24h' => $this->boolean('is_24h'),


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
                'description' => 'Nome completo do usuário que será o dono do estabelecimento.',
                'example' => 'João Paulo Alacantara'
            ],
            'email' => [
                'description' => 'Email do usuário para login.',
                'example' => 'alcantara@gmail.com'
            ],
            'birth_date' => [
                'description' => 'Data de nascimento do usuário (deve ser maior de 18 anos).',
                'example' => '1990-08-28'
            ],
            'password' => [
                'description' => 'Senha para acesso ao sistema (mínimo 8 caracteres).',
                'example' => 'senhaForte123!'
            ],
            'password_confirmation' => [
                'description' => 'Confirmação da senha.',
                'example' => 'senhaForte123!'
            ],
            'avatar' => [
                'description' => 'Ficheiro de imagem para o avatar (opcional).',
            ],
            'phone_number' => [
                'description' => 'Número do telefone.',
                'example' => '+5541991858466'
            ],

            'establishment_name' => [
                'description' => 'Nome fantasia do estabelecimento.',
                'example' => 'Sabor Divino'
            ],
            'category_id' => [
                'description' => 'ID da categoria principal deste estabelecimento.',
                'example' => 1
            ],
            'site' => [
                'description' => 'Web site do estabelicimento',
                'example' => 'https://minhaempresa.com.br'
            ],
            'instagram' => [
                'description' => 'Instagram da empresa.',
                'example' => 'https://www.instagram.com/iomargorges/'
            ],
            'is_ecommerce' => [
                'description' => 'Informe se essa empresa possuí e-commerce. ',
                'example' => '',
            ],
            'has_appointment' => [
                'description' => 'Informe se essa empresa possuí agendamento de horário. ',
                'example' => '',
            ],
            'is_24h' => [
                'description' => 'Informe se essa empresa atende 24 horas. ',
                'example' => '',
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
            'auto_approve_code' => [
                'description' => 'Se você possui um código de pré aprovação informe aqui.',
                'example' => 'ac65432'
            ],
        ];
    }
}
