<?php

namespace App\Http\Resources\Api\V1\DeletedRecord;

use Carbon\Carbon;
use App\Traits\FormatsDates;
use Illuminate\Http\Request;
use App\Models\LogSubjectType;
use Illuminate\Http\Resources\Json\JsonResource;

class DeletedRecordResource extends JsonResource
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

        $subject = $this->subject; // Acesso ao sujeito através do método existente
        $formattedData = json_decode($this->subject_data, true);
        return [
            'id' => $this->id,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'causer_name' => optional($this->causer)->name, // Nome do causer se disponível
            'subject_data' => $this->formatData($formattedData),
            'deleted_at' => $this->formatDate($this->deleted_at),
        ];
    }

    /**
     * Formata o conteúdo do campo data para exibição.
     */
    protected function formatData($data)
    {
        if (!is_array($data)) {
            return 'Data format not recognized';
        }

        return collect($data)->mapWithKeys(function ($value, $key) {
            // return [ucfirst(str_replace('_', ' ', $key)) => $value];
            return [$key => $value];
        });
    }
}
