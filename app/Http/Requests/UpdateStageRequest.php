<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStageRequest extends FormRequest
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
        $stageId = $this->route('stage')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('stages', 'name')->ignore($stageId)],
            'sequence' => ['required', 'integer', 'min:1', Rule::unique('stages', 'sequence')->ignore($stageId)],
            'sla_value' => ['required', 'integer', 'min:1'],
            'sla_unit' => ['required', 'in:hours,days'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
