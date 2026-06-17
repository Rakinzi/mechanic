<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $makes = ['Toyota', 'Honda', 'BMW', 'Nissan', 'Ford'];
        $models = ['Corolla', 'Civic', 'X5', 'Qashqai', 'Ranger'];
        $colors = ['White', 'Black', 'Silver', 'Blue', 'Red'];
        $idx = ($counter - 1) % 5;

        return [
            'client_id' => Client::factory(),
            'registration_number' => strtoupper('AB'.str_pad((string) $counter, 2, '0', STR_PAD_LEFT).'CD'.$counter),
            'vin' => strtoupper('VIN'.str_pad((string) $counter, 14, '0', STR_PAD_LEFT)),
            'make' => $makes[$idx],
            'model' => $models[$idx],
            'model_year' => 2015 + ($counter % 10),
            'color' => $colors[$idx],
            'odometer_km' => 50000 + ($counter * 10000),
            'notes' => null,
        ];
    }
}
