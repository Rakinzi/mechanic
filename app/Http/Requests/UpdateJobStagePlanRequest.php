<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobStagePlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'assigned_mechanic_id' => $this->filled('assigned_mechanic_id') ? (int) $this->input('assigned_mechanic_id') : null,
            'planned_duration_value' => $this->filled('planned_duration_value') ? (int) $this->input('planned_duration_value') : null,
        ]);
    }

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
            'assigned_mechanic_id' => ['nullable', 'integer', 'exists:users,id'],
            'planned_duration_value' => ['required', 'integer', 'min:1', 'max:365'],
            'planned_duration_unit' => ['required', 'in:hours,days'],
            'latest_note' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
