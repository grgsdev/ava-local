<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterCompanyRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('Master') ?? false; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255']]; }
}
