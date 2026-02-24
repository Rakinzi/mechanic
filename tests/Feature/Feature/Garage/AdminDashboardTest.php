<?php

namespace Tests\Feature\Feature\Garage;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_admin_dashboard(): void
    {
        $this->withoutVite();

        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_mechanic_cannot_access_admin_dashboard(): void
    {
        $this->withoutVite();

        $this->seed(RolesAndPermissionsSeeder::class);

        $mechanic = User::factory()->create();
        $mechanic->assignRole('mechanic');

        $response = $this->actingAs($mechanic)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }
}
