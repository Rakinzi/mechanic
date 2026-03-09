<?php

namespace Tests\Feature\Feature\Garage;

use App\Models\Client;
use App\Models\JobCard;
use App\Models\JobCardAudit;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_card_creation_writes_audit_entry(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);
        $panelBeating = Stage::query()->where('name', 'Panel Beating')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.job-cards.store'), [
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'customer_complaint' => 'Needs full body restoration.',
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $panelBeating->id,
                    'planned_duration_value' => 2,
                    'planned_duration_unit' => 'days',
                ],
            ],
        ]);

        $response->assertRedirect();

        $jobCard = JobCard::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('job_card_audits', [
            'job_card_id' => $jobCard->id,
            'actor_id' => $admin->id,
            'event' => 'JOB_CARD_CREATED',
        ]);
    }

    public function test_future_stage_update_and_close_write_audit_entries(): void
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
            'sequence' => 1,
            'status' => \App\Enums\StageStatus::NotStarted,
        ]);

        $updateResponse = $this->actingAs($admin)->patch(route('admin.job-cards.stages.update', [$jobCard, $futureStage]), [
            'assigned_mechanic_id' => $mechanic->id,
            'planned_duration_value' => 5,
            'planned_duration_unit' => 'hours',
            'latest_note' => 'Reassigned for specialist work.',
        ]);

        $updateResponse->assertRedirect();

        $closeResponse = $this->actingAs($admin)->post(route('admin.job-cards.close', $jobCard));

        $closeResponse->assertRedirect();

        $this->assertDatabaseHas('job_card_audits', [
            'job_card_id' => $jobCard->id,
            'job_stage_id' => $futureStage->id,
            'actor_id' => $admin->id,
            'event' => 'FUTURE_STAGE_UPDATED',
        ]);

        $this->assertDatabaseHas('job_card_audits', [
            'job_card_id' => $jobCard->id,
            'actor_id' => $admin->id,
            'event' => 'JOB_CARD_CLOSED',
        ]);

        $this->assertGreaterThanOrEqual(2, JobCardAudit::query()->where('job_card_id', $jobCard->id)->count());
    }
}
