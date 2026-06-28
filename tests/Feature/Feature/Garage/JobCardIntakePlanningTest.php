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

        $technician = User::factory()->create();
        $technician->assignRole('mechanic');

        $response = $this->actingAs($admin)->post(route('admin.job-cards.store'), [
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'customer_complaint' => 'Front and rear bodywork plus repaint.',
            'diagnosis_notes' => 'Route through selected stations only.',
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $inspection->id,
                    'mechanic_ids' => [],
                    'planned_duration_value' => 4,
                    'planned_duration_unit' => 'hours',
                ],
                [
                    'enabled' => 1,
                    'stage_id' => $painting->id,
                    'mechanic_ids' => [$technician->id],
                    'planned_duration_value' => 3,
                    'planned_duration_unit' => 'days',
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
        $this->assertSame($technician->id, $jobCard->jobStages[1]->assigned_mechanic_id);
        $this->assertSame($jobCard->jobStages[0]->id, $jobCard->current_job_stage_id);
        $this->assertDatabaseHas('job_stage_mechanics', [
            'job_stage_id' => $jobCard->jobStages[1]->id,
            'user_id' => $technician->id,
        ]);
    }

    public function test_admin_can_assign_multiple_technicians_to_a_stage(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);
        $stage = Stage::query()->where('name', 'Panel Beating')->firstOrFail();

        $technician1 = User::factory()->create();
        $technician1->assignRole('mechanic');
        $technician2 = User::factory()->create();
        $technician2->assignRole('mechanic');

        $this->actingAs($admin)->post(route('admin.job-cards.store'), [
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'customer_complaint' => 'Heavy panel damage.',
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $stage->id,
                    'mechanic_ids' => [$technician1->id, $technician2->id],
                    'planned_duration_value' => 2,
                    'planned_duration_unit' => 'days',
                ],
            ],
        ])->assertRedirect();

        $jobCard = JobCard::query()->with('jobStages')->latest('id')->firstOrFail();
        $jobStage = $jobCard->jobStages->first();

        $this->assertSame($technician1->id, $jobStage->assigned_mechanic_id);
        $this->assertDatabaseHas('job_stage_mechanics', ['job_stage_id' => $jobStage->id, 'user_id' => $technician1->id]);
        $this->assertDatabaseHas('job_stage_mechanics', ['job_stage_id' => $jobStage->id, 'user_id' => $technician2->id]);
    }

    public function test_admin_can_update_future_stage_plan_but_not_started_stage(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $technician = User::factory()->create();
        $technician->assignRole('mechanic');

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
            'assigned_mechanic_id' => $technician->id,
            'sequence' => 2,
            'status' => StageStatus::InProgress,
        ]);

        $allowedResponse = $this->actingAs($admin)->patch(route('admin.job-cards.stages.update', [$jobCard, $futureStage]), [
            'mechanic_ids' => [$technician->id],
            'planned_duration_value' => 2,
            'planned_duration_unit' => 'days',
            'latest_note' => 'Move to the paint specialist.',
        ]);

        $allowedResponse->assertRedirect();
        $this->assertDatabaseHas('job_stages', [
            'id' => $futureStage->id,
            'assigned_mechanic_id' => $technician->id,
            'planned_duration_value' => 2,
            'planned_duration_unit' => 'days',
        ]);
        $this->assertDatabaseHas('job_stage_mechanics', [
            'job_stage_id' => $futureStage->id,
            'user_id' => $technician->id,
        ]);

        $blockedResponse = $this->actingAs($admin)->patch(route('admin.job-cards.stages.update', [$jobCard, $startedStage]), [
            'mechanic_ids' => [],
            'planned_duration_value' => 1,
            'planned_duration_unit' => 'hours',
        ]);

        $blockedResponse->assertForbidden();
    }

    public function test_submitting_non_technician_user_as_technician_id_is_rejected(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);
        $stage = Stage::query()->where('name', 'Panel Beating')->firstOrFail();

        $clientUser = User::factory()->create();
        $clientUser->assignRole('client');

        $response = $this->actingAs($admin)->post(route('admin.job-cards.store'), [
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'customer_complaint' => 'Paint scratches.',
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $stage->id,
                    'mechanic_ids' => [$clientUser->id],
                    'planned_duration_value' => 1,
                    'planned_duration_unit' => 'days',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('selected_stages.0.mechanic_ids.0');
    }

    public function test_updating_future_stage_with_empty_technician_ids_clears_all_technicians(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $technician = User::factory()->create();
        $technician->assignRole('mechanic');

        $jobCard = JobCard::factory()->create([
            'created_by' => $admin->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'status' => 'OPEN',
        ]);

        $futureStage = JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => Stage::factory()->create(['sequence' => 30])->id,
            'assigned_mechanic_id' => $technician->id,
            'sequence' => 1,
            'status' => StageStatus::NotStarted,
        ]);

        $futureStage->mechanics()->sync([$technician->id]);

        $this->actingAs($admin)->patch(route('admin.job-cards.stages.update', [$jobCard, $futureStage]), [
            'mechanic_ids' => [],
            'planned_duration_value' => 1,
            'planned_duration_unit' => 'hours',
        ])->assertRedirect();

        $this->assertDatabaseHas('job_stages', [
            'id' => $futureStage->id,
            'assigned_mechanic_id' => null,
        ]);
        $this->assertDatabaseMissing('job_stage_mechanics', [
            'job_stage_id' => $futureStage->id,
        ]);
    }

    public function test_admin_can_create_job_card_with_new_vehicle_for_existing_client(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        Vehicle::factory()->create([
            'client_id' => $client->id,
            'registration_number' => 'OLD-111',
        ]);

        $stage = Stage::query()->where('name', 'Panel Beating')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.job-cards.store'), [
            'client_id' => $client->id,
            'customer_complaint' => 'Second vehicle needs assessment.',
            'diagnosis_notes' => 'Existing client arrived with another car.',
            'vehicle' => [
                'registration_number' => 'NEW-222',
                'make' => 'Honda',
                'model' => 'Fit',
                'model_year' => 2020,
                'color' => 'Silver',
            ],
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $stage->id,
                    'mechanic_ids' => [],
                    'planned_duration_value' => 1,
                    'planned_duration_unit' => 'days',
                ],
            ],
        ]);

        $response->assertRedirect();

        $vehicle = Vehicle::query()->where('registration_number', 'NEW-222')->firstOrFail();

        $this->assertSame($client->id, $vehicle->client_id);
        $this->assertDatabaseCount('vehicles', 2);
        $this->assertDatabaseHas('job_cards', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function test_vehicle_must_belong_to_selected_client_when_creating_job_card(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $selectedClient = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $otherVehicle = Vehicle::factory()->create([
            'client_id' => $otherClient->id,
        ]);

        $stage = Stage::query()->where('name', 'Panel Beating')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.job-cards.store'), [
            'client_id' => $selectedClient->id,
            'vehicle_id' => $otherVehicle->id,
            'customer_complaint' => 'Mismatched vehicle test.',
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $stage->id,
                    'mechanic_ids' => [],
                    'planned_duration_value' => 1,
                    'planned_duration_unit' => 'days',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('job_cards', 0);
    }

    public function test_admin_can_be_assigned_to_release_stage(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);
        $releaseStage = Stage::query()->where('name', 'Release')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.job-cards.store'), [
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'customer_complaint' => 'Full restoration.',
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $releaseStage->id,
                    'mechanic_ids' => [$admin->id],
                    'planned_duration_value' => 1,
                    'planned_duration_unit' => 'hours',
                ],
            ],
        ]);

        $response->assertRedirect();

        $jobCard = JobCard::query()->with('jobStages')->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $jobCard->jobStages->first()->assigned_mechanic_id);
    }

    public function test_admin_cannot_be_assigned_to_non_release_stage(): void
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
            'customer_complaint' => 'Dents.',
            'selected_stages' => [
                [
                    'enabled' => 1,
                    'stage_id' => $panelBeating->id,
                    'mechanic_ids' => [$admin->id],
                    'planned_duration_value' => 1,
                    'planned_duration_unit' => 'days',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('selected_stages.0.mechanic_ids.0');
    }
}
