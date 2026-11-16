<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInteractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A autorização será feita no controller, podemos deixar como true aqui.
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:single_choice,multiple_choice,drag_and_drop,hotspot,auto',
            'timestamp' => 'required|numeric|min:0',
            'payload' => 'required|array',
            'payload.question' => 'required_if:type,single_choice,multiple_choice,drag_and_drop|string',
            'payload.answers' => 'required_if:type,single_choice,multiple_choice|array',
            // 'payload.answers.*.action' => 'required|string|in:continue,jump_to_time,jump_to_video',
            // 'payload.answers.*.target' => 'nullable|numeric',

            'payload.draggables' => 'required_if:type,drag_and_drop|array|min:1',
            'payload.draggables.*.id' => 'required|string',
            'payload.draggables.*.text' => 'required|string',

            'payload.dropzones' => 'required_if:type,drag_and_drop|array|min:1',
            'payload.dropzones.*.id' => 'required|string',
            'payload.dropzones.*.label' => 'required|string',
            'payload.dropzones.*.correct_item_id' => 'required|string',

            'payload.duration' => 'required_if:type,hotspot|numeric|min:1',
            'payload.position' => 'required_if:type,hotspot|array',
            'payload.position.x' => 'required_if:type,hotspot|numeric',
            'payload.position.y' => 'required_if:type,hotspot|numeric',
            'payload.size' => 'required_if:type,hotspot|array',
            'payload.size.width' => 'required_if:type,hotspot|numeric',
            'payload.size.height' => 'required_if:type,hotspot|numeric',
            'payload.action' => 'required_if:type,hotspot|string|in:continue,jump_to_time,jump_to_video',
            'payload.target' => 'nullable|numeric',


            'payload.loop' => 'sometimes|array',
            'payload.loop.enabled' => 'sometimes|boolean',
            'payload.loop.start_time' => 'required_if:payload.loop.enabled,true|numeric|min:0',

            'payload.action' => 'required_if:type,auto|string|in:jump_to_time,jump_to_video',
            'payload.target' => 'required_if:type,auto|numeric', // Pode ser segundos ou ID do vídeo
        ];
    }
}
