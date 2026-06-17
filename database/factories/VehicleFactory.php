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
        return [
            'client_id' => Client::factory(),
            'registration_number' => strtoupper($this->faker->bothify('??##???')),
            'vin' => strtoupper($this->faker->bothify('###############??')),
            'make' => $this->faker->randomElement(['Toyota', 'Honda', 'BMW', 'Nissan', 'Ford']),
            'model' => $this->faker->randomElement(['Corolla', 'Civic', 'X5', 'Qashqai', 'Ranger']),
            'model_year' => $this->faker->numberBetween(2008, 2025),
            'color' => $this->faker->safeColorName(),
            'odometer_km' => $this->faker->numberBetween(10000, 220000),
            'notes' => $this->faker->sentence(),
        ];
    }
}
