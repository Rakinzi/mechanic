<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:stages,name'],
            'sequence' => ['required', 'integer', 'min:1', 'unique:stages,sequence'],
            'sla_value' => ['required', 'integer', 'min:1'],
            'sla_unit' => ['required', 'in:hours,days'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
