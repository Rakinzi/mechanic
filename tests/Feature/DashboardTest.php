<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admins_are_redirected_to_admin_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_technicians_are_redirected_to_assigned_stages_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('technician');
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('technician.assigned-stages.index'));
    }

    public function test_clients_are_redirected_to_repairs_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('client');
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('client.repairs.index'));
    }

    public function test_users_without_a_role_are_forbidden_from_dashboard_redirect(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertForbidden();
    }
}
