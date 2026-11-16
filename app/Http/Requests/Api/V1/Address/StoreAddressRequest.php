<?php

namespace App\Http\Requests\Api\V1\Address;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Address;
use App\Services\DateService;
/**
 * @version V1
 */
class StoreAddressRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => ['required', 'string', 'max:50'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2']
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => 0,
            
        ]);

        
    }

    public function bodyParameters()
    {
        return [
            'type' => [
                        'description' => 'Tipo do endereço (ex: comercial, residencial, cobrança).',
                        'example' => 'comercial'
                ],
            'zip_code' => [
                        'description' => 'CEP (Código de Endereçamento Postal).',
                        'example' => '80000-000'
                ],
            'street' => [
                        'description' => 'Nome da rua/logradouro.',
                        'example' => 'Rua das Flores'
                ],
            'number' => [
                        'description' => 'Número do endereço.',
                        'example' => '123'
                ],
            'complement' => [
                        'description' => 'Complemento do endereço (ex: Sala 10, Bloco B).',
                        'example' => 'Loja 2'
                ],
            'neighborhood' => [
                        'description' => 'Bairro.',
                        'example' => 'Centro'
                ],
            'city' => [
                        'description' => 'Cidade.',
                        'example' => 'Curitiba'
                ],
            'state' => [
                        'description' => 'Sigla do estado (UF).',
                        'example' => 'PR'
                ],
            
        ];
    }
}