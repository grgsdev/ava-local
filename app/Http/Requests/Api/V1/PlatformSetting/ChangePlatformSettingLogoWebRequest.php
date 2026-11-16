<?php

namespace App\Http\Requests\Api\V1\PlatformSetting;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @version V1 generated
 */
class ChangePlatformSettingLogoWebRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $supportedImageMimes = implode(',', config('image-file.supported_images'));

        return [
            'logo_web' => 'required|image|mimes:' . $supportedImageMimes . '|max:2048',
        ];
    }

    public function bodyParameters()
    {
        return [
            'logo_web' => [
                'description' => 'No description provided.',
            ],
        ];
    }
}