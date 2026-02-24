<?php

namespace Database\Factories;

use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobCard>
 */
class JobCardFactory extends Factory
{
    protected $model = JobCard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'created_by' => User::factory(),
            'job_number' => 'JC-'.now()->format('Ymd').'-'.str_pad((string) fake()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_complaint' => fake()->sentence(10),
            'diagnosis_notes' => fake()->sentence(12),
            'status' => fake()->randomElement(['OPEN', 'COMPLETED']),
            'received_at' => now()->subDays(2),
            'promised_delivery_at' => now()->addDays(3),
            'closed_at' => null,
        ];
    }
}
