<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IntakeJobCardRequest extends FormRequest
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
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client.name' => ['required_without:client_id', 'string', 'max:255'],
            'client.email' => ['nullable', 'email', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:40'],
            'client.address' => ['nullable', 'string', 'max:500'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
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
            'assigned_mechanics' => ['nullable', 'array'],
            'assigned_mechanics.*' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
