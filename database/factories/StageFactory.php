<?php

namespace Database\Factories;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stage>
 */
class StageFactory extends Factory
{
    protected $model = Stage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = $this->faker->unique()->numberBetween(1, 6);

        return [
            'name' => 'Stage '.$sequence,
            'sequence' => $sequence,
            'sla_value' => $this->faker->numberBetween(4, 24),
            'sla_unit' => 'hours',
            'is_active' => true,
        ];
    }
}
