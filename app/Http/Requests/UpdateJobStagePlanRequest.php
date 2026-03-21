<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobStagePlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mechanic_ids' => collect((array) $this->input('mechanic_ids', []))
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all(),
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
            'mechanic_ids' => ['nullable', 'array'],
            'mechanic_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->whereIn('id', function ($query): void {
                    $query->select('model_id')
                        ->from('model_has_roles')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->where('roles.name', 'mechanic')
                        ->where('model_has_roles.model_type', \App\Models\User::class);
                }),
            ],
            'planned_duration_value' => ['required', 'integer', 'min:1', 'max:365'],
            'planned_duration_unit' => ['required', 'in:hours,days'],
            'latest_note' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
