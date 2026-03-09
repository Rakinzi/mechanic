<?php

namespace Tests\Feature\Feature\Garage;

use App\Models\Client;
use App\Models\JobCard;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobCardSummaryDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_job_card_summary(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        [$admin, $jobCard] = $this->makeJobCardFixture();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.job-cards.summary', $jobCard));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee($jobCard->job_number);
    }

    public function test_client_can_open_owned_job_card_summary(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        [$admin, $jobCard] = $this->makeJobCardFixture();
        $admin->assignRole('admin');

        $clientUser = User::factory()->create();
        $clientUser->assignRole('client');
        $jobCard->vehicle->client->update(['user_id' => $clientUser->id]);

        $response = $this->actingAs($clientUser)->get(route('client.repairs.summary', $jobCard));

        $response->assertOk();
        $response->assertSee($jobCard->job_number);
    }

    public function test_client_cannot_open_another_clients_job_card_summary(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        [$admin, $jobCard] = $this->makeJobCardFixture();
        $admin->assignRole('admin');

        $ownerUser = User::factory()->create();
        $ownerUser->assignRole('client');
        $jobCard->vehicle->client->update(['user_id' => $ownerUser->id]);

        $otherClient = User::factory()->create();
        $otherClient->assignRole('client');

        $response = $this->actingAs($otherClient)->get(route('client.repairs.summary', $jobCard));

        $response->assertForbidden();
    }

    /**
     * @return array{0: User, 1: JobCard}
     */
    protected function makeJobCardFixture(): array
    {
        $admin = User::factory()->create();
        $client = Client::factory()->create();
        $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);
        $jobCard = JobCard::factory()->create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $admin->id,
        ]);

        Stage::query()->where('name', 'Panel Beating')->firstOrFail();
        Stage::query()->where('name', 'Mechanics')->firstOrFail();

        return [$admin, $jobCard];
    }
}
