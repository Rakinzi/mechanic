<?php

namespace Tests\Feature\Feature\Garage;

use App\Enums\DelayReasonCategory;
use App\Enums\DelayReportStatus;
use App\Enums\StageStatus;
use App\Models\DelayReport;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DelayReportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelayReportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mechanic_can_submit_delay_report(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        [$mechanic, $jobStage] = $this->makeAssignedStage();

        $service = app(DelayReportService::class);

        $delayReport = $service->submit($jobStage, $mechanic, [
            'reason_category' => DelayReasonCategory::PartsDelay->value,
            'explanation' => 'Parts are delayed by external supplier.',
            'proposed_eta' => now()->addHours(6),
        ]);

        $this->assertEquals(DelayReportStatus::Pending, $delayReport->status);
        $this->assertEquals(StageStatus::Overdue, $jobStage->fresh()->status);
    }

    public function test_admin_can_approve_delay_report(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        [$mechanic, $jobStage] = $this->makeAssignedStage();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $delayReport = DelayReport::factory()->create([
            'job_stage_id' => $jobStage->id,
            'submitted_by' => $mechanic->id,
            'status' => DelayReportStatus::Pending,
            'proposed_eta' => now()->addHours(8),
        ]);

        $service = app(DelayReportService::class);
        $service->approve($delayReport, $admin, 'Approved ETA extension.');

        $this->assertEquals(DelayReportStatus::Approved, $delayReport->fresh()->status);
    }

    /**
     * @return array{0: User, 1: JobStage}
     */
    protected function makeAssignedStage(): array
    {
        $mechanic = User::factory()->create();
        $mechanic->assignRole('mechanic');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $stage = Stage::factory()->create(['sequence' => 1]);

        $jobCard = JobCard::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'created_by' => $admin->id,
        ]);

        $jobStage = JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => $stage->id,
            'assigned_mechanic_id' => $mechanic->id,
            'sequence' => 1,
            'status' => StageStatus::InProgress,
            'started_at' => now()->subHours(4),
            'due_at' => now()->subMinute(),
        ]);

        return [$mechanic, $jobStage];
    }
}
