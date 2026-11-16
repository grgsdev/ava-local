<?php

namespace App\Http\Requests\Api\V1\PlatformSetting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PlatformSetting;
use App\Services\DateService;

/**
 * @version V1
 */
class UpdatePlatformSettingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'platform_name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_info' => ['sometimes', 'nullable', 'json'],
            'social_links' => ['sometimes', 'nullable', 'json'],
            'physical_address' => ['sometimes', 'nullable', 'string'],
            'checkin_cooldown_minutes' => ['sometimes', 'nullable', 'integer'],
            'max_checkin_distance_meters' => ['sometimes', 'nullable', 'integer'],
            'free_user_max_points' => ['sometimes', 'nullable', 'integer'],
            'free_user_max_level' => ['sometimes', 'nullable', 'integer'],
            'free_user_max_coins' => ['sometimes', 'nullable', 'integer'],
            'logo_web' => ['sometimes', 'nullable', 'image', 'max:20480'],
            'logo_mobile' => ['sometimes', 'nullable', 'image', 'max:20480']
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([]);
    }

    public function bodyParameters()
    {
        return [
            'platform_name' => [
                'description' => 'No description provided.',
                'example' => 'Clube Lupa'
            ],
            'contact_info' => [
                'description' => 'JSON com dados de contato (email, whatsapp).',
                'example' => 'No example provided.'
            ],
            'social_links' => [
                'description' => 'JSON com links de redes sociais (instagram, landing_page).',
                'example' => 'No example provided.'
            ],
            'physical_address' => [
                'description' => 'No description provided.',
                'example' => 'No example provided.'
            ],
            'checkin_cooldown_minutes' => [
                'description' => 'No description provided.',
                'example' => 'No example provided.'
            ],
            'max_checkin_distance_meters' => [
                'description' => 'No description provided.',
                'example' => 'No example provided.'
            ],
            'free_user_max_points' => [
                'description' => 'No description provided.',
                'example' => 'No example provided.'
            ],
            'free_user_max_level' => [
                'description' => 'No description provided.',
                'example' => 'No example provided.'
            ],
            'free_user_max_coins' => [
                'description' => 'No description provided.',
                'example' => 'No example provided.'
            ],
            'logo_web' => [
                'description' => 'No description provided.',
            ],
            'logo_mobile' => [
                'description' => 'No description provided.',
            ],

        ];
    }
}
