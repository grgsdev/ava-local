<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Services\DateService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @version V1
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Qualquer um pode tentar se registrar
    }

    public function rules(): array
    {
        $eighteenYearsAgo = now()->subYears(18)->format('Y-m-d');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'birth_date' => ['required', 'date', 'before_or_equal:' . $eighteenYearsAgo],
            'phone_number' => ['required', 'string'],

            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'], // Optional referral code
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
            'name' => ['description' => 'Nome do usuário.', 'example' => 'Ana Silva'],
            'email' => ['description' => 'E-mail único do usuário.', 'example' => 'ana@exemplo.com'],
            'birth_date' => [
                'description' => 'Data de nascimento do usuário (deve ser maior de 18 anos).',
                'example' => '1990-08-28'
            ],
            'phone_number' => [
                'description' => 'Número do telefone.',
                'example' => '+5541991858466'
            ],

            'password' => ['description' => 'Senha com no mínimo 8 caracteres.', 'example' => 'password123'],
            'password_confirmation' => ['description' => 'Confirmação da senha.', 'example' => 'password123'],
            'referral_code' => ['description' => 'Código de referência do usuário indicador se houver.', 'example' => 'X3O4KBJI'],

            'avatar' => ['description' => 'Arquivo de imagem para o avatar (opcional, max:2MB).'],
        ];
    }
}
