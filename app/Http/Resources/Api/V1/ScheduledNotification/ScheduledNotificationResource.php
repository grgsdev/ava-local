<?php

namespace App\Http\Resources\Api\V1\ScheduledNotification;

use App\Traits\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    use FormatsDates;
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'target_roles' => $this->target_roles,
            'delivery_channels' => $this->delivery_channels,
            'start_date' => $this->formatDate($this->start_date),
            'period_in_days' => $this->period_in_days,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
