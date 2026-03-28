<?php

namespace Tests\Feature\Feature\Garage;

use App\Enums\DelayReportStatus;
use App\Enums\StageStatus;
use App\Models\DelayReport;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\JobStageService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class StageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_stage_cannot_complete_without_approved_delay_report(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        [$mechanic, $jobStage] = $this->makeOverdueAssignedStage();

        $service = app(JobStageService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Overdue stage requires an approved delay report before completion.');

        $service->complete($jobStage->fresh('jobCard'), $mechanic);
    }

    public function test_overdue_stage_can_complete_after_approved_delay_report(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        [$mechanic, $jobStage] = $this->makeOverdueAssignedStage();

        DelayReport::factory()->create([
            'job_stage_id' => $jobStage->id,
            'submitted_by' => $mechanic->id,
            'status' => DelayReportStatus::Approved,
        ]);

        $service = app(JobStageService::class);
        $service->complete($jobStage->fresh('jobCard'), $mechanic);

        $this->assertEquals(StageStatus::Completed, $jobStage->fresh()->status);
    }

    public function test_scheduler_command_marks_in_progress_stage_as_overdue(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        [, $jobStage] = $this->makeOverdueAssignedStage(StageStatus::InProgress);

        $jobStage->update([
            'status' => StageStatus::InProgress,
        ]);

        $this->artisan('stages:mark-overdue')->assertExitCode(0);

        $this->assertEquals(StageStatus::Overdue, $jobStage->fresh()->status);
    }

    public function test_secondary_assigned_mechanic_can_view_and_start_shared_stage(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        [$primaryMechanic, $secondaryMechanic, $jobStage] = $this->makeSharedAssignedStage();

        $this->actingAs($secondaryMechanic)
            ->get(route('mechanic.assigned-stages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mechanic/AssignedStages')
                ->where('stages.0.uuid', $jobStage->uuid)
                ->where('stages.0.assigned_mechanic_id', $primaryMechanic->id)
            );

        $this->actingAs($secondaryMechanic)
            ->get(route('mechanic.assigned-stages.show', $jobStage))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mechanic/StageExecution')
                ->where('jobStage.uuid', $jobStage->uuid)
            );

        $this->actingAs($secondaryMechanic)
            ->post(route('mechanic.job-stages.start', $jobStage))
            ->assertRedirect()
            ->assertSessionHas('success', 'Stage started successfully.');

        $this->assertEquals(StageStatus::InProgress, $jobStage->fresh()->status);
    }

    public function test_start_action_returns_session_error_when_previous_stage_is_incomplete(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $mechanic = User::factory()->create();
        $mechanic->assignRole('mechanic');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $jobCard = JobCard::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'created_by' => $admin->id,
            'status' => 'OPEN',
        ]);

        JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => Stage::factory()->create(['sequence' => 101, 'name' => 'Workflow Test Stage 101'])->id,
            'assigned_mechanic_id' => $mechanic->id,
            'sequence' => 1,
            'status' => StageStatus::NotStarted,
        ]);

        $blockedStage = JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => Stage::factory()->create(['sequence' => 102, 'name' => 'Workflow Test Stage 102'])->id,
            'assigned_mechanic_id' => $mechanic->id,
            'sequence' => 2,
            'status' => StageStatus::NotStarted,
        ]);

        $blockedStage->mechanics()->sync([$mechanic->id]);

        $this->actingAs($mechanic)
            ->from(route('mechanic.assigned-stages.show', $blockedStage))
            ->post(route('mechanic.job-stages.start', $blockedStage))
            ->assertRedirect(route('mechanic.assigned-stages.show', $blockedStage))
            ->assertSessionHasErrors(['stage' => 'Previous stage must be completed first.']);

        $this->assertEquals(StageStatus::NotStarted, $blockedStage->fresh()->status);
    }

    /**
     * @return array{0: User, 1: JobStage}
     */
    protected function makeOverdueAssignedStage(StageStatus $status = StageStatus::Overdue): array
    {
        $mechanic = User::factory()->create();
        $mechanic->assignRole('mechanic');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $stage = Stage::query()->where('name', 'Panel Beating')->firstOrFail();

        $jobCard = JobCard::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'created_by' => $admin->id,
        ]);

        $jobStage = JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => $stage->id,
            'assigned_mechanic_id' => $mechanic->id,
            'sequence' => 1,
            'status' => $status,
            'started_at' => now()->subDay(),
            'due_at' => now()->subHour(),
        ]);

        return [$mechanic, $jobStage];
    }

    /**
     * @return array{0: User, 1: User, 2: JobStage}
     */
    protected function makeSharedAssignedStage(): array
    {
        $primaryMechanic = User::factory()->create();
        $primaryMechanic->assignRole('mechanic');

        $secondaryMechanic = User::factory()->create();
        $secondaryMechanic->assignRole('mechanic');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $jobCard = JobCard::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'created_by' => $admin->id,
            'status' => 'OPEN',
        ]);

        $jobStage = JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => Stage::factory()->create(['sequence' => 103, 'name' => 'Workflow Test Stage 103'])->id,
            'assigned_mechanic_id' => $primaryMechanic->id,
            'sequence' => 1,
            'status' => StageStatus::NotStarted,
        ]);

        $jobStage->mechanics()->sync([$primaryMechanic->id, $secondaryMechanic->id]);

        return [$primaryMechanic, $secondaryMechanic, $jobStage];
    }
}
