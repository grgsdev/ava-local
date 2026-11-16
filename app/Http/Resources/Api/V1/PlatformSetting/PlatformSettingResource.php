<?php

namespace App\Http\Resources\Api\V1\PlatformSetting;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

use App\Traits\FormatsDates;

/**
 * @version V1
 */
class PlatformSettingResource extends JsonResource
{
    use FormatsDates;
    public static $wrap = null;
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'platform_name' => $this->platform_name,
            'contact_info' => $this->contact_info,
            'social_links' => $this->social_links,
            'physical_address' => $this->physical_address,
            'logo_web_url' => $this->when($this->hasMedia('logo_web'), fn() => $this->getFirstMediaUrl('logo_web')),
            'logo_mobile_url' => $this->when($this->hasMedia('logo_mobile'), fn() => $this->getFirstMediaUrl('logo_mobile')),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at)
        ];
    }
}
