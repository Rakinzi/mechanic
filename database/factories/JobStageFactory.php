<?php

namespace Database\Factories;

use App\Enums\StageStatus;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobStage>
 */
class JobStageFactory extends Factory
{
    protected $model = JobStage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'stage_id' => Stage::factory(),
            'assigned_mechanic_id' => User::factory(),
            'sequence' => fake()->numberBetween(1, 6),
            'status' => StageStatus::NotStarted,
            'started_at' => null,
            'paused_at' => null,
            'due_at' => null,
            'completed_at' => null,
            'last_status_changed_at' => now(),
            'latest_note' => null,
        ];
    }
}
