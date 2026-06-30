<?php

namespace App\Http\Requests;

use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IntakeJobCardRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $selectedStages = collect($this->input('selected_stages', []))
            ->filter(fn (mixed $stage): bool => filled(data_get($stage, 'enabled')) && filled(data_get($stage, 'stage_id')))
            ->map(fn (mixed $stage): array => [
                'stage_id' => (int) data_get($stage, 'stage_id'),
                'technician_ids' => collect((array) data_get($stage, 'technician_ids', []))
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all(),
                'planned_duration_value' => (int) data_get($stage, 'planned_duration_value'),
                'planned_duration_unit' => data_get($stage, 'planned_duration_unit', 'hours'),
            ])
            ->values()
            ->all();

        $this->merge([
            'selected_stages' => $selectedStages,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $releaseStageId = Stage::query()->where('name', 'Release')->value('id');

            foreach ($this->input('selected_stages', []) as $index => $selectedStage) {
                $stageId = (int) data_get($selectedStage, 'stage_id');
                $isReleaseStage = $stageId === (int) $releaseStageId;

                foreach ((array) data_get($selectedStage, 'technician_ids', []) as $userIndex => $userId) {
                    $user = User::query()->find((int) $userId);

                    if (! $user) {
                        continue;
                    }

                    $allowed = $isReleaseStage
                        ? $user->hasAnyRole(['technician', 'admin'])
                        : $user->hasRole('technician');

                    if (! $allowed) {
                        $validator->errors()->add(
                            "selected_stages.{$index}.technician_ids.{$userIndex}",
                            $isReleaseStage
                                ? 'The selected user must be a technician or admin.'
                                : 'The selected user must be a technician.',
                        );
                    }
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
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client.name' => ['required_without:client_id', 'string', 'max:255'],
            'client.email' => ['nullable', 'email', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:40'],
            'client.address' => ['nullable', 'string', 'max:500'],
            'vehicle_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicles', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! filled($value) || ! $this->filled('client_id')) {
                        return;
                    }

                    $belongsToClient = Vehicle::query()
                        ->whereKey((int) $value)
                        ->where('client_id', $this->integer('client_id'))
                        ->exists();

                    if (! $belongsToClient) {
                        $fail('The selected vehicle does not belong to the chosen client.');
                    }
                },
            ],
            'vehicle.registration_number' => ['required_without:vehicle_id', 'string', 'max:100'],
            'vehicle.vin' => ['nullable', 'string', 'max:100'],
            'vehicle.make' => ['required_without:vehicle_id', 'string', 'max:100'],
            'vehicle.model' => ['required_without:vehicle_id', 'string', 'max:100'],
            'vehicle.model_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'vehicle.color' => ['nullable', 'string', 'max:100'],
            'vehicle.odometer_km' => ['nullable', 'integer', 'min:0'],
            'customer_complaint' => ['required', 'string'],
            'diagnosis_notes' => ['nullable', 'string'],
            'promised_delivery_at' => ['nullable', 'date', 'after_or_equal:today'],
            'selected_stages' => ['required', 'array', 'min:1'],
            'selected_stages.*.stage_id' => ['required', 'integer', 'distinct', 'exists:stages,id'],
            'selected_stages.*.technician_ids' => ['nullable', 'array'],
            'selected_stages.*.technician_ids.*' => ['integer', 'exists:users,id'],
            'selected_stages.*.planned_duration_value' => ['required', 'integer', 'min:1', 'max:365'],
            'selected_stages.*.planned_duration_unit' => ['required', 'in:hours,days'],
        ];
    }
}
