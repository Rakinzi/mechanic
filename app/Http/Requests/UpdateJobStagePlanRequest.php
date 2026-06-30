<?php

namespace App\Http\Requests;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobStagePlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'technician_ids' => collect((array) $this->input('technician_ids', []))
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

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $jobStage = $this->route('jobStage');
            $releaseStageId = Stage::query()->where('name', 'Release')->value('id');
            $isReleaseStage = $jobStage && (int) $jobStage->stage_id === (int) $releaseStageId;

            foreach ((array) $this->input('technician_ids', []) as $index => $userId) {
                $user = User::query()->find((int) $userId);

                if (! $user) {
                    continue;
                }

                $allowed = $isReleaseStage
                    ? $user->hasAnyRole(['technician', 'admin'])
                    : $user->hasRole('technician');

                if (! $allowed) {
                    $validator->errors()->add(
                        "technician_ids.{$index}",
                        $isReleaseStage
                            ? 'The selected user must be a technician or admin.'
                            : 'The selected user must be a technician.',
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'technician_ids' => ['nullable', 'array'],
            'technician_ids.*' => ['integer', 'exists:users,id'],
            'planned_duration_value' => ['required', 'integer', 'min:1', 'max:365'],
            'planned_duration_unit' => ['required', 'in:hours,days'],
            'latest_note' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
