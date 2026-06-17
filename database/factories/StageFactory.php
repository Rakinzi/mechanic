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
        static $counter = 0;
        $counter++;

        return [
            'name' => 'Stage '.$counter,
            'sequence' => $counter,
            'sla_value' => 8,
            'sla_unit' => 'hours',
            'is_active' => true,
        ];
    }
}
