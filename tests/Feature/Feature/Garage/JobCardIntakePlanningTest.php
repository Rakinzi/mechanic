<?php

namespace Tests\Feature\Feature\Garage;

use App\Enums\StageStatus;
use App\Models\Client;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobCardIntakePlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_job_card_with_selected_stages_and_planned_durations(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);

        $inspection = Stage::query()->where('name', 'Panel Beating')->firstOrFail();
        $painting = Stage::query()->where('name', 'Spraypainting')->firstOrFail();

        $mechanic = User::factory()->create();
        $mechanic->assignRole('mechanic');

        $response = $this->actingAs($admin)->post(route('admin.job-cards.store'), [
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'customer_complaint' => 'Front and rear bodywork plus repaint.',
            'diagnosis_notes' => 'Route through selected stations only.',
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $painting->id,
                    'assigned_mechanic_id' => $mechanic->id,
                    'planned_duration_value' => 3,
                    'planned_duration_unit' => 'days',
                ],
                [
                    'enabled' => 1,
                    'stage_id' => $inspection->id,
                    'assigned_mechanic_id' => null,
                    'planned_duration_value' => 4,
                    'planned_duration_unit' => 'hours',
                ],
            ],
        ]);

        $response->assertRedirect();

        $jobCard = JobCard::query()->with('jobStages.stage')->latest('id')->firstOrFail();

        $this->assertCount(2, $jobCard->jobStages);
        $this->assertSame($inspection->id, $jobCard->jobStages[0]->stage_id);
        $this->assertSame(1, $jobCard->jobStages[0]->sequence);
        $this->assertSame(4, $jobCard->jobStages[0]->planned_duration_value);
        $this->assertSame('hours', $jobCard->jobStages[0]->planned_duration_unit);
        $this->assertSame($painting->id, $jobCard->jobStages[1]->stage_id);
        $this->assertSame(2, $jobCard->jobStages[1]->sequence);
        $this->assertSame($mechanic->id, $jobCard->jobStages[1]->assigned_mechanic_id);
        $this->assertSame($jobCard->jobStages[0]->id, $jobCard->current_job_stage_id);
    }

    public function test_admin_can_update_future_stage_plan_but_not_started_stage(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $mechanic = User::factory()->create();
        $mechanic->assignRole('mechanic');

        $jobCard = JobCard::factory()->create([
            'created_by' => $admin->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'status' => 'OPEN',
        ]);

        $futureStage = JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => Stage::factory()->create(['sequence' => 20])->id,
            'assigned_mechanic_id' => null,
            'sequence' => 1,
            'status' => StageStatus::NotStarted,
        ]);

        $startedStage = JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => Stage::factory()->create(['sequence' => 21])->id,
            'assigned_mechanic_id' => $mechanic->id,
            'sequence' => 2,
            'status' => StageStatus::InProgress,
        ]);

        $allowedResponse = $this->actingAs($admin)->patch(route('admin.job-cards.stages.update', [$jobCard, $futureStage]), [
            'assigned_mechanic_id' => $mechanic->id,
            'planned_duration_value' => 2,
            'planned_duration_unit' => 'days',
            'latest_note' => 'Move to the paint specialist.',
        ]);

        $allowedResponse->assertRedirect();
        $this->assertDatabaseHas('job_stages', [
            'id' => $futureStage->id,
            'assigned_mechanic_id' => $mechanic->id,
            'planned_duration_value' => 2,
            'planned_duration_unit' => 'days',
        ]);

        $blockedResponse = $this->actingAs($admin)->patch(route('admin.job-cards.stages.update', [$jobCard, $startedStage]), [
            'assigned_mechanic_id' => null,
            'planned_duration_value' => 1,
            'planned_duration_unit' => 'hours',
        ]);

        $blockedResponse->assertForbidden();
    }
}
