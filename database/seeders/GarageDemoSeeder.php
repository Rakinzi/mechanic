<?php

namespace Database\Seeders;

use App\Enums\DelayReasonCategory;
use App\Enums\DelayReportStatus;
use App\Enums\StageStatus;
use App\Models\Client;
use App\Models\DelayReport;
use App\Models\JobCard;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class GarageDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@garage.test'],
            ['name' => 'Garage Admin', 'password' => 'password', 'email_verified_at' => now()]
        );
        $admin->assignRole('admin');

        $mechanics = collect([
            ['name' => 'Mechanic One', 'email' => 'mechanic1@garage.test'],
            ['name' => 'Mechanic Two', 'email' => 'mechanic2@garage.test'],
            ['name' => 'Mechanic Three', 'email' => 'mechanic3@garage.test'],
        ])->map(function (array $data): User {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => 'password', 'email_verified_at' => now()]
            );
            $user->assignRole('mechanic');

            return $user;
        });

        $defaultStages = [
            ['name' => 'Panel Beating', 'sequence' => 1, 'sla_value' => 12, 'sla_unit' => 'hours'],
            ['name' => 'Mechanics', 'sequence' => 2, 'sla_value' => 8, 'sla_unit' => 'hours'],
            ['name' => 'Spraypainting', 'sequence' => 3, 'sla_value' => 10, 'sla_unit' => 'hours'],
            ['name' => 'Buffing', 'sequence' => 4, 'sla_value' => 4, 'sla_unit' => 'hours'],
            ['name' => 'Carwash', 'sequence' => 5, 'sla_value' => 2, 'sla_unit' => 'hours'],
            ['name' => 'Waxing n Polishing', 'sequence' => 6, 'sla_value' => 3, 'sla_unit' => 'hours'],
            ['name' => 'Release', 'sequence' => 7, 'sla_value' => 1, 'sla_unit' => 'hours'],
        ];

        foreach ($defaultStages as $stage) {
            Stage::query()->updateOrCreate(['name' => $stage['name']], $stage + ['is_active' => true]);
        }

        Client::factory()->count(3)->create()->each(function (Client $client) use ($mechanics, $admin): void {
            $clientUser = User::factory()->create([
                'name' => $client->name,
                'email' => $client->email,
                'email_verified_at' => now(),
                'password' => 'password',
            ]);

            $clientUser->assignRole('client');
            $client->update(['user_id' => $clientUser->id]);

            $vehicle = Vehicle::factory()->create([
                'client_id' => $client->id,
            ]);

            $jobCard = JobCard::factory()->create([
                'vehicle_id' => $vehicle->id,
                'created_by' => $admin->id,
                'status' => 'OPEN',
            ]);

            $this->createJobStages($jobCard, $mechanics);
        });
    }

    /**
     * @param  Collection<int, User>  $mechanics
     */
    protected function createJobStages(JobCard $jobCard, Collection $mechanics): void
    {
        $stages = Stage::query()->where('is_active', true)->orderBy('sequence')->get();

        foreach ($stages as $index => $stage) {
            $status = StageStatus::NotStarted;
            $startedAt = null;
            $dueAt = null;
            $completedAt = null;

            if ($index === 0) {
                $status = StageStatus::Completed;
                $startedAt = now()->subDays(2);
                $dueAt = now()->subDay();
                $completedAt = now()->subDay();
            }

            if ($index === 1) {
                $status = StageStatus::Overdue;
                $startedAt = now()->subDay();
                $dueAt = now()->subHours(2);
            }

            $jobStage = $jobCard->jobStages()->create([
                'stage_id' => $stage->id,
                'assigned_mechanic_id' => $mechanics->random()->id,
                'sequence' => $stage->sequence,
                'status' => $status,
                'started_at' => $startedAt,
                'due_at' => $dueAt,
                'completed_at' => $completedAt,
                'last_status_changed_at' => now(),
            ]);

            if ($status === StageStatus::Overdue) {
                DelayReport::query()->create([
                    'job_stage_id' => $jobStage->id,
                    'submitted_by' => $jobStage->assigned_mechanic_id,
                    'reason_category' => DelayReasonCategory::PartsDelay,
                    'explanation' => 'Awaiting parts delivery from supplier warehouse.',
                    'proposed_eta' => now()->addHours(6),
                    'status' => DelayReportStatus::Pending,
                ]);
            }
        }
    }
}
