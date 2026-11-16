<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformSettingResource extends JsonResource
{
    public static $wrap = null;
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {


        return [
            'platform_name' => $this->platform_name,
            'contact_info' => $this->contact_info, // O cast no modelo já converte para array
            'social_links' => $this->social_links, // O cast no modelo já converte para array
            'physical_address' => $this->physical_address,
            'checkin_cooldown_minutes' => $this->checkin_cooldown_minutes,
            'max_checkin_distance_meters' => $this->max_checkin_distance_meters,
            'free_user_max_level' => $this->free_user_max_level,
            'logo_web_url' => $this->getFirstMediaUrl('logo_web'),
            'logo_mobile_url' => $this->getFirstMediaUrl('logo_mobile'),
        ];
    }
}
