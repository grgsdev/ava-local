<?php

namespace App\Http\Resources\Api\V1\CommandLog;

use Carbon\Carbon;
use App\Traits\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommandLogResource extends JsonResource
{
    use FormatsDates;
    public static $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'command' => $this->command,
            'output' => $this->output,
            'status' => $this->status,
            'executed_at' => $this->formatDate($this->executed_at),
        ];
    }
}
