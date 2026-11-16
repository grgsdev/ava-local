<?php

namespace App\Http\Resources\Api\V1\ActivityLog;

use Carbon\Carbon;
use App\Traits\FormatsDates;
use Illuminate\Http\Request;
use App\Models\LogSubjectType;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
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
        //        $subjectTypeAlias = LogSubjectType::where('type', $this->subject_type)->value('alias');
        $subject = $this->subject; // Acesso ao sujeito através do método existente
        $subjectName = $subject ? ($subject->name ?? $subject->title ?? 'Unknown') : 'Unknown';

        return [
            'id' => $this->id,
            'subject_type' => $this->subject_type,
            'subject_type_alias' => 'Unknown', // Valor padrão se o alias não existir
            'event' => $this->event,
            'subject_id' => $this->subject_id,
            'subject_name' => $subjectName,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'causer_name' => optional($this->causer)->name, // Nome do causer se disponível
            'properties' => $this->properties,
            'causer' => $this->causer,
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),


            'description' => $this->description,
        ];
    }
}
