<?php

namespace Tests\Feature\Feature\Garage;

use App\Enums\StageStatus;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminReportingAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reports_page_includes_analytics_payloads(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        [$admin] = $this->makeAdminAndJobCards();

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Reports')
                ->has('summary.totalJobCards')
                ->has('analytics.stage_turnaround')
                ->has('analytics.delays_by_mechanic')
                ->has('analytics.common_delay_reasons')
            );
    }

    public function test_admin_job_cards_overdue_filter_returns_overdue_jobs_only(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        [$admin, $overdueJobCard] = $this->makeAdminAndJobCards();

        $response = $this->actingAs($admin)->get(route('admin.job-cards.index', [
            'overdue_only' => 1,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/JobCardsIndex')
                ->where('jobCards.data.0.job_number', $overdueJobCard->job_number)
                ->missing('jobCards.data.1')
                ->where('filters.overdue_only', true)
            );
    }

    /**
     * @return array{0: User, 1: JobCard, 2: JobCard}
     */
    protected function makeAdminAndJobCards(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $stage = Stage::factory()->create(['sequence' => 1, 'name' => 'Inspection']);

        $overdueJobCard = JobCard::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'created_by' => $admin->id,
            'job_number' => 'JC-OVERDUE',
        ]);

        JobStage::factory()->create([
            'job_card_id' => $overdueJobCard->id,
            'stage_id' => $stage->id,
            'sequence' => 1,
            'status' => StageStatus::Overdue,
            'due_at' => now()->subHour(),
        ]);

        $normalJobCard = JobCard::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'created_by' => $admin->id,
            'job_number' => 'JC-NORMAL',
        ]);

        JobStage::factory()->create([
            'job_card_id' => $normalJobCard->id,
            'stage_id' => $stage->id,
            'sequence' => 1,
            'status' => StageStatus::InProgress,
            'due_at' => now()->addHours(4),
        ]);

        return [$admin, $overdueJobCard, $normalJobCard];
    }
}
