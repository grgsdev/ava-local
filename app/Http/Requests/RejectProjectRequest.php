<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project && ($this->user()?->can('reject', $project) ?? false);
    }

    public function rules(): array
    {
        return [
            'feedback' => ['required', 'string', 'max:2000'],
        ];
    }
}
