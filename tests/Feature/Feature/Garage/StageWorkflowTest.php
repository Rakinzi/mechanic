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
}
