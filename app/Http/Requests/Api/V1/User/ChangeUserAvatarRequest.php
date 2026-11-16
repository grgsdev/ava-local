<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @version V1 generated
 */
class ChangeUserAvatarRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $supportedImageMimes = implode(',', config('image-file.supported_images'));

        return [
            'avatar' => 'required|image|mimes:' . $supportedImageMimes . '|max:2048',
        ];
    }

    public function bodyParameters()
    {
        return [
            'avatar' => [
                'description' => 'Imagem de perfil do usuário.',
            ],
        ];
    }
}