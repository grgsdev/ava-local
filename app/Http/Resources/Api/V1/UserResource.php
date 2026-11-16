<?php

namespace App\Http\Resources\Api\V1;

use App\Traits\FormatsDates;
use Illuminate\Http\Request;

use App\Http\Resources\System\RoleResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\Address\AddressResource;
use App\Http\Resources\Api\V1\ActivityLog\ActivityLogResource;


/**
 * @version V1
 */
class UserResource extends JsonResource
{
    use FormatsDates;
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->formatDate($this->email_verified_at),
            'nickname' => $this->nickname,
            'username' => $this->username,
            'is_payed' => $this->is_payed,
            //            'pin_code' => $this->pin_code,
            'referral_code' => $this->referral_code,
            'referred_by_user_id' => $this->referred_by_user_id,
            'remember_token' => $this->remember_token,
            'phone_number' => $this->phone_number,
            'birth_date' => $this->formatDate($this->birth_date),
            'avatar_url' => $this->when($this->hasMedia('avatar'), fn() => $this->getFirstMediaUrl('avatar')),
            'is_affiliate' => $this->is_affiliate,
            'send_notification' => $this->send_notification,
            'send_email' => $this->send_email,
            'points_balance' => $this->points_balance,
            'purchase_balance_cents' => $this->purchase_balance_cents,
            'coins_balance' => $this->coins_balance,            // 'is_active' => $this->is_active,
            // 'is_active_text' => $this->is_active_text,
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
            // 'deleted_at' => $this->formatDate($this->deleted_at),
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'establishments' => SimpleEstablishmentResource::collection($this->whenLoaded('establishments')),

            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            // 'active_motives' => ActiveMotiveResource::collection($this->whenLoaded('activeMotives')),
            'activities' => ActivityLogResource::collection($this->whenLoaded('activities'))
        ];
    }
}
