<?php

namespace Database\Factories;

use App\Enums\DelayReasonCategory;
use App\Enums\DelayReportStatus;
use App\Models\DelayReport;
use App\Models\JobStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DelayReport>
 */
class DelayReportFactory extends Factory
{
    protected $model = DelayReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_stage_id' => JobStage::factory(),
            'submitted_by' => User::factory(),
            'reviewed_by' => null,
            'reason_category' => DelayReasonCategory::Other,
            'explanation' => fake()->paragraph(),
            'proposed_eta' => now()->addDay(),
            'status' => DelayReportStatus::Pending,
            'review_comment' => null,
            'reviewed_at' => null,
        ];
    }
}
